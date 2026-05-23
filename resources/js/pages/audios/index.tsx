import { Head, InfiniteScroll, Link } from '@inertiajs/react';
import { Music2, Upload } from 'lucide-react';
import { useState } from 'react';
import AudioPlayer from '@/components/audio-player';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { index as audios } from '@/routes/audios';
import { create as uploadsCreate } from '@/routes/uploads';
import type { Paginated, UploadListItem } from '@/types/upload';

type AudiosIndexProps = {
    uploads: Paginated<UploadListItem>;
};

function formatUploadedAt(uploadedAt: string | null): string {
    if (!uploadedAt) {
        return 'Unknown';
    }

    return new Date(uploadedAt).toLocaleString();
}

function displayName(upload: UploadListItem): string {
    return upload.metadata?.title ?? upload.original_name;
}

export default function AudiosIndex({ uploads }: AudiosIndexProps) {
    const [selectedUuid, setSelectedUuid] = useState<string | null>(
        uploads.data[0]?.uuid ?? null,
    );

    const selectedUpload =
        uploads.data.find((upload) => upload.uuid === selectedUuid) ??
        uploads.data[0] ??
        null;

    return (
        <>
            <Head title="Audios" />

            <div className="flex min-h-0 flex-1 flex-col">
                <div className="flex flex-1 flex-col gap-4 overflow-x-auto overflow-y-auto p-4">
                {uploads.data.length === 0 ? (
                    <Card>
                        <CardHeader className="items-center text-center">
                            <div className="bg-muted mb-2 flex size-12 items-center justify-center rounded-full">
                                <Music2 className="text-muted-foreground size-6" />
                            </div>
                            <CardTitle>No ready audios yet</CardTitle>
                            <CardDescription>
                                Upload an audio file and wait for processing to
                                finish.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex justify-center">
                            <Button asChild>
                                <Link href={uploadsCreate()} prefetch>
                                    <Upload />
                                    Upload audio
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <Card>
                            <CardHeader className="flex flex-row items-start justify-between gap-4">
                                <div className="space-y-1">
                                    <CardTitle>Your audios</CardTitle>
                                    <CardDescription>
                                        {uploads.meta.total} ready{' '}
                                        {uploads.meta.total === 1
                                            ? 'upload'
                                            : 'uploads'}
                                    </CardDescription>
                                </div>
                                <Button asChild size="sm">
                                    <Link href={uploadsCreate()} prefetch>
                                        <Upload />
                                        Upload
                                    </Link>
                                </Button>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                <InfiniteScroll
                                    data="uploads"
                                    itemsElement="#uploads-table-body"
                                    next={({ loading, fetch, hasMore }) =>
                                        hasMore ? (
                                            <div className="flex justify-center pt-4">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={fetch}
                                                    disabled={loading}
                                                >
                                                    {loading
                                                        ? 'Loading...'
                                                        : 'Load more'}
                                                </Button>
                                            </div>
                                        ) : null
                                    }
                                >
                                    <div className="overflow-x-auto">
                                        <table className="w-full min-w-[640px] text-sm">
                                            <thead className="bg-muted/50 border-b">
                                                <tr>
                                                    <th className="px-4 py-3 text-left font-medium">
                                                        Title
                                                    </th>
                                                    <th className="px-4 py-3 text-left font-medium">
                                                        Artist
                                                    </th>
                                                    <th className="px-4 py-3 text-left font-medium">
                                                        Duration
                                                    </th>
                                                    <th className="px-4 py-3 text-left font-medium">
                                                        Uploaded
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody id="uploads-table-body">
                                                {uploads.data.map((upload) => {
                                                    const isSelected =
                                                        upload.uuid ===
                                                        selectedUpload?.uuid;

                                                    return (
                                                        <tr
                                                            key={upload.uuid}
                                                            onClick={() =>
                                                                setSelectedUuid(
                                                                    upload.uuid,
                                                                )
                                                            }
                                                            className={cn(
                                                                'hover:bg-muted/50 cursor-pointer border-b transition-colors last:border-b-0',
                                                                isSelected &&
                                                                    'bg-primary/5',
                                                            )}
                                                        >
                                                            <td className="px-4 py-3 font-medium">
                                                                {displayName(
                                                                    upload,
                                                                )}
                                                            </td>
                                                            <td className="text-muted-foreground px-4 py-3">
                                                                {upload.metadata
                                                                    ?.artist ??
                                                                    '—'}
                                                            </td>
                                                            <td className="text-muted-foreground px-4 py-3">
                                                                {upload.metadata
                                                                    ?.duration ??
                                                                    '—'}
                                                            </td>
                                                            <td className="text-muted-foreground px-4 py-3">
                                                                {formatUploadedAt(
                                                                    upload.uploaded_at,
                                                                )}
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>
                                </InfiniteScroll>
                            </CardContent>
                        </Card>
                    </>
                )}
                </div>

                {selectedUpload && (
                    <AudioPlayer
                        key={selectedUpload.uuid}
                        upload={selectedUpload}
                        variant="bar"
                    />
                )}
            </div>
        </>
    );
}

AudiosIndex.layout = {
    breadcrumbs: [
        {
            title: 'Audios',
            href: audios(),
        },
    ],
};
