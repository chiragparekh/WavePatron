import type { CreatorProfileVisibility } from '@/types/creator-profile-visibility';

export type CreatorProfileFormData = {
    handle: string;
    display_name: string;
    tagline: string | null;
    bio: string | null;
    categories: string[];
    website: string | null;
    social_links: Record<string, string>;
    support_email: string | null;
    visibility: CreatorProfileVisibility;
    avatar_url?: string | null;
    cover_image_url?: string | null;
};

export type PublicCreatorProfile = {
    handle: string;
    display_name: string;
    tagline: string | null;
    bio: string | null;
    avatar_url: string | null;
    cover_image_url: string | null;
    categories: string[];
    website: string | null;
    social_links: Record<string, string>;
    support_email: string | null;
};

export type CreatorAudioPlaceholder = {
    id: string;
    title: string;
    access_level: 'free' | 'premium';
};

export type CreatorTierPlaceholder = {
    id: string;
    name: string;
    benefits: string[];
    monthly_price: string;
    annual_price: string | null;
    is_subscribable: boolean;
    subscribe_url: string | null;
};
