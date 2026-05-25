export type TierStatus =
    | 'draft'
    | 'requested'
    | 'approved'
    | 'rejected'
    | 'active'
    | 'archived';

export type CreatorTierListItem = {
    id: number;
    name: string;
    status: TierStatus;
    monthly_price: string;
    annual_price: string | null;
    is_editable: boolean;
    can_submit: boolean;
    can_activate: boolean;
    can_archive: boolean;
};

export type CreatorTierDetail = {
    id: number;
    name: string;
    benefits: string[];
    monthly_price: string;
    annual_price: string | null;
    status: TierStatus;
    is_editable: boolean;
    can_submit: boolean;
    can_activate: boolean;
    can_archive: boolean;
    is_subscribable: boolean;
};

export type PublicTier = {
    id: string;
    name: string;
    benefits: string[];
    monthly_price: string;
    annual_price: string | null;
    is_subscribable: boolean;
};
