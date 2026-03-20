<?php

namespace App\Http\Controllers;

use App\Models\ServiceVisit;
use App\Models\ServiceVisitIngredient;
use App\Models\ServiceVisitPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ServiceVisitController extends Controller
{
    /**
     * Список визитов обслуживания для терминала.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'terminal_id' => ['required', 'integer', 'exists:vendista_terminals,id'],
        ]);

        $visits = ServiceVisit::where('terminal_id', $request->terminal_id)
            ->with([
                'ingredients.ingredient',
                'photos',
                'user:id,name',
            ])
            ->orderByDesc('visited_at')
            ->limit(50)
            ->get();

        return response()->json(['visits' => $visits]);
    }

    /**
     * Сохранение нового визита обслуживания.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'terminal_id' => ['required', 'integer', 'exists:vendista_terminals,id'],
            'visited_at' => ['required', 'date'],
            'water_main' => ['nullable', 'numeric', 'between:0,1'],
            'water_spare' => ['nullable', 'numeric', 'between:0,1'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'photo_inside' => ['nullable', 'image', 'max:10240'],
            'photo_outside' => ['nullable', 'image', 'max:10240'],
            'comment_photos' => ['nullable', 'array'],
            'comment_photos.*' => ['image', 'max:10240'],
            'ingredients' => ['nullable', 'json'],
        ]);

        // Декодирование ингредиентов из JSON
        $ingredientsData = [];
        if (!empty($validated['ingredients'])) {
            $ingredientsData = json_decode($validated['ingredients'], true);
            if (!is_array($ingredientsData)) {
                return response()->json(['message' => 'Некорректный формат ингредиентов'], 422);
            }
        }

        $visit = DB::transaction(function () use ($validated, $ingredientsData) {
            // Создание визита
            $visit = ServiceVisit::create([
                'terminal_id' => $validated['terminal_id'],
                'user_id' => Auth::id(),
                'visited_at' => $validated['visited_at'],
                'water_main' => $validated['water_main'] ?? null,
                'water_spare' => $validated['water_spare'] ?? null,
                'comment' => $validated['comment'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ]);

            // Сохранение ингредиентов (только если brought > 0 или needed > 0)
            foreach ($ingredientsData as $item) {
                $brought = (int) ($item['brought'] ?? 0);
                $needed = (int) ($item['needed'] ?? 0);

                if ($brought > 0 || $needed > 0) {
                    ServiceVisitIngredient::create([
                        'service_visit_id' => $visit->id,
                        'ingredient_id' => $item['ingredient_id'],
                        'brought' => $brought,
                        'needed' => $needed,
                    ]);
                }
            }

            return $visit;
        });

        // Сохранение фотографий (за пределами транзакции — файловые операции)
        if ($request->hasFile('photo_inside')) {
            $this->savePhoto($visit, $request->file('photo_inside'), 'inside');
        }

        if ($request->hasFile('photo_outside')) {
            $this->savePhoto($visit, $request->file('photo_outside'), 'outside');
        }

        if ($request->hasFile('comment_photos')) {
            foreach ($request->file('comment_photos') as $file) {
                $this->savePhoto($visit, $file, 'comment');
            }
        }

        // Ротация старых фотографий
        $this->rotatePhotos($visit->terminal_id);

        $visit->load(['ingredients.ingredient', 'photos', 'user:id,name']);

        return response()->json(['visit' => $visit], 201);
    }

    /**
     * Детальная информация о визите.
     *
     * @param ServiceVisit $visit
     * @return JsonResponse
     */
    public function show(ServiceVisit $visit): JsonResponse
    {
        $visit->load(['ingredients.ingredient', 'photos', 'user:id,name']);

        return response()->json(['visit' => $visit]);
    }

    /**
     * Сжатие и сохранение фотографии визита.
     *
     * @param ServiceVisit $visit
     * @param UploadedFile $file
     * @param string $type Тип фото: inside, outside, comment
     */
    private function savePhoto(ServiceVisit $visit, UploadedFile $file, string $type): void
    {
        $originalName = $file->getClientOriginalName();
        $filename = $type . '_' . uniqid() . '.jpg';
        $directory = "visits/{$visit->terminal_id}/{$visit->id}";
        $path = "{$directory}/{$filename}";

        // Сжатие через GD
        $imageData = $this->compressImage($file->getPathname());

        Storage::disk('public')->put($path, $imageData);

        ServiceVisitPhoto::create([
            'service_visit_id' => $visit->id,
            'type' => $type,
            'path' => $path,
            'original_name' => $originalName,
        ]);
    }

    /**
     * Сжатие изображения: ресайз если сторона > 1920px, JPEG quality 75%.
     *
     * @param string $sourcePath Путь к исходному файлу
     * @return string Бинарные данные сжатого JPEG
     */
    private function compressImage(string $sourcePath): string
    {
        $imageInfo = getimagesize($sourcePath);
        $mimeType = $imageInfo['mime'] ?? '';

        // Создание ресурса GD из исходного файла
        $source = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png' => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            'image/gif' => imagecreatefromgif($sourcePath),
            default => imagecreatefromjpeg($sourcePath),
        };

        $width = imagesx($source);
        $height = imagesy($source);
        $maxSide = 1920;

        // Ресайз если одна из сторон больше 1920px
        if ($width > $maxSide || $height > $maxSide) {
            if ($width >= $height) {
                $newWidth = $maxSide;
                $newHeight = (int) round($height * ($maxSide / $width));
            } else {
                $newHeight = $maxSide;
                $newWidth = (int) round($width * ($maxSide / $height));
            }

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
        }

        // Сохранение в буфер как JPEG quality 75%
        ob_start();
        imagejpeg($source, null, 75);
        $data = ob_get_clean();
        imagedestroy($source);

        return $data;
    }

    /**
     * Ротация фотографий: оставить фото только для 3 последних визитов терминала.
     * Более старые фото удаляются (файлы + записи БД).
     *
     * @param int $terminalId
     */
    private function rotatePhotos(int $terminalId): void
    {
        // ID последних 3 визитов с фотографиями для данного терминала
        $recentVisitIds = ServiceVisit::where('terminal_id', $terminalId)
            ->whereHas('photos')
            ->orderByDesc('visited_at')
            ->limit(3)
            ->pluck('id');

        // Фотографии более старых визитов
        $oldPhotos = ServiceVisitPhoto::whereHas('visit', function ($query) use ($terminalId, $recentVisitIds) {
            $query->where('terminal_id', $terminalId)
                ->whereNotIn('id', $recentVisitIds);
        })->get();

        foreach ($oldPhotos as $photo) {
            Storage::disk('public')->delete($photo->path);
            $photo->delete();
        }
    }
}
