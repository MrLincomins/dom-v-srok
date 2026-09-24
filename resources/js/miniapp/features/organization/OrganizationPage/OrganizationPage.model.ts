import { texts } from '@/app/texts';

export const ORGANIZATION_SECTIONS = [
    { to: '/organization/contacts', title: texts.organization.contacts, hint: texts.organization.contactsHint },
    {
        to: '/organization/contracts',
        title: texts.organization.contracts,
        hint: texts.organization.contractsHintShort,
    },
    { to: '/organization/houses', title: texts.organization.houses, hint: texts.organization.housesHint },
    {
        to: '/organization/executors',
        title: texts.organization.executors,
        hint: texts.organization.executorsHint,
    },
    {
        to: '/organization/contractors',
        title: texts.organization.contractors,
        hint: texts.organization.contractorsHintShort,
    },
] as const;

export type OrganizationSectionKey = 'contacts' | 'contracts' | 'houses' | 'executors' | 'contractors';

const SECTION_KEYS = new Set<string>(ORGANIZATION_SECTIONS.map((item) => item.to.slice('/organization/'.length)));

export function isOrganizationSection(value: string | undefined): value is OrganizationSectionKey {
    return value != null && SECTION_KEYS.has(value);
}

export function sectionTitle(section: OrganizationSectionKey): string {
    return ORGANIZATION_SECTIONS.find((item) => item.to === `/organization/${section}`)?.title ?? texts.organization.title;
}
