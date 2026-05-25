export type CreatorProfileVisibility = 'draft' | 'public' | 'hidden';

export const creatorProfileVisibilityOptions: {
    value: CreatorProfileVisibility;
    label: string;
}[] = [
    { value: 'draft', label: 'Draft' },
    { value: 'public', label: 'Public' },
    { value: 'hidden', label: 'Hidden' },
];
