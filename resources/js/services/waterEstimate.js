/**
 * Единая идемпотентная функция оценки остатка воды в бутылках терминала.
 * Используется для отображения на главной и для предзаполнения формы обслуживания.
 *
 * Логика:
 *  - Без разветвителя (default): сначала тратится основная бутылка, потом запасная.
 *    На каждый стакан -- WATER_PER_CUP_ML.
 *  - С разветвителем (settings.water_split): обе бутылки расходуются параллельно
 *    по WATER_PER_CUP_ML / 2 за стакан, не «перетекая» одна в другую.
 *
 * Возвращает доли [0..1] без округления. Для отображения вызывающий код
 * сам решает, как форматировать (toFixed/roundWater).
 */

export const BOTTLE_VOLUME_ML = 18900;
export const WATER_PER_CUP_ML = 340;

/**
 * @param {object} terminal - объект терминала с полями:
 *   - latest_visit: { water_main, water_spare } | null
 *   - sales_since_last_visit: number
 *   - settings: { water_split: boolean } | null
 * @returns {{ main: number, spare: number }} доли заполнения [0..1]
 */
export function estimateWater(terminal) {
    const lv = terminal?.latest_visit;
    if (!lv) return { main: 0, spare: 0 };

    const mainMl = (Number(lv.water_main) || 0) * BOTTLE_VOLUME_ML;
    const spareMl = (Number(lv.water_spare) || 0) * BOTTLE_VOLUME_ML;
    const sales = terminal?.sales_since_last_visit ?? 0;

    let remainingMain;
    let remainingSpare;

    if (terminal?.settings?.water_split) {
        const perBottleMl = sales * (WATER_PER_CUP_ML / 2);
        remainingMain = Math.max(0, mainMl - perBottleMl);
        remainingSpare = Math.max(0, spareMl - perBottleMl);
    } else {
        const usedMl = sales * WATER_PER_CUP_ML;
        remainingMain = mainMl - usedMl;
        remainingSpare = spareMl;
        if (remainingMain < 0) {
            remainingSpare = Math.max(0, spareMl + remainingMain);
            remainingMain = 0;
        }
    }

    return {
        main: Math.min(1, Math.max(0, remainingMain / BOTTLE_VOLUME_ML)),
        spare: Math.min(1, Math.max(0, remainingSpare / BOTTLE_VOLUME_ML)),
    };
}

/**
 * Округление доли до 0.1 (используется при предзаполнении формы обслуживания
 * и для отображения единообразно во всех местах).
 */
export function roundWater(value) {
    return Math.round(value * 10) / 10;
}
