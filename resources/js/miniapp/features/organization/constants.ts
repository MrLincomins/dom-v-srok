export const CONTRACTS = ['cold_water', 'hot_water', 'heat', 'power', 'tko'] as const;
export const CONTRACTOR_TYPES = ['lift', 'intercom', 'tko', 'other'] as const;
export type ContractorType = (typeof CONTRACTOR_TYPES)[number];
