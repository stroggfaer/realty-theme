export const buildQuery = (params = {}) =>
    new URLSearchParams(
        Object.entries(params).filter(([_, v]) => v !== undefined && v !== null)
    ).toString();

export const declension = (forms, number) => {
    const count = Math.abs(number);
    const cases = [2, 0, 1, 1, 1, 2];
    const chosenCase = (count % 100 > 4 && count % 100 < 20) ? 2 : cases[(count % 10) < 5 ? count % 5 : 5];

    return {
        count: number,
        text: forms[chosenCase]
    };
}