export type UploadListMetadata = {
    title: string | null;
    artist: string | null;
    duration: string | null;
    duration_seconds: number | null;
    codec: string | null;
    bitrate: number | null;
    sample_rate: number | null;
};

export type UploadListItem = {
    uuid: string;
    original_name: string;
    uploaded_at: string | null;
    metadata: UploadListMetadata | null;
    hls_playlist_url: string;
    waveform_url: string;
    creator?: {
        display_name: string;
        handle: string;
    } | null;
};

export type UploadProcessingItem = {
    uuid: string;
    original_name: string;
    status: 'processing' | 'failed';
    step_statuses: Record<string, string>;
    uploaded_at: string | null;
};

export type UploadStats = {
    total_ready: number;
    total_processing: number;
    total_failed: number;
    total_storage_bytes: number;
};

export type WaveformData = {
    version: number;
    length: number;
    data: [number, number][];
};

export type CreatorAudioListItem = {
    uuid: string;
    title: string | null;
    original_name: string;
    display_title: string;
    status: string;
    publish_status: 'draft' | 'published';
    access_level: 'free' | 'premium';
    uploaded_at: string | null;
    can_publish: boolean;
};

export type CreatorAudioDetail = CreatorAudioListItem & {
    description: string | null;
    metadata: {
        title: string | null;
        artist: string | null;
        duration: string | null;
    } | null;
    hls_playlist_url: string | null;
    waveform_url: string | null;
};

export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};
