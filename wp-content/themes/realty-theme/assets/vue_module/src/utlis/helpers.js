export const buildQuery = (params = {}) =>
    new URLSearchParams(
        Object.entries(params).filter(([_, v]) => v !== undefined && v !== null)
    ).toString();