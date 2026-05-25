import { Head, InfiniteScroll, Link, router, usePoll } from '@inertiajs/react';
import {
    CheckCircle2,
    Circle,
    Loader2,
    Music2,
    Upload,
    XCircle,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import AudioPlayer from '@/components/audio-player';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import {
    ToggleGroup,
    ToggleGroupItem,
} from '@/components/ui/toggle-group';
import { cn } from '@/lib/utils';
import { index as audios } from '@/routes/audios';
import { create as uploadsCreate } from '@/routes/uploads';
import type {
    Paginated,
    UploadListItem,
    UploadProcessingItem,
} from '@/types/upload';

const STATUS_POLL_INTERVAL_MS = 2000;

type AudiosTab = 'ready' | 'processing';

type AudiosIndexProps = {
    uploads: Paginated<UploadListItem>;
    processingUploads?: UploadProcessingItem[];
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

function formatStepLabel(step: string): string {
    return step.replace(/_/g, ' ');
}

function isStepComplete(status: string): boolean {
    return status === 'completed' || status === 'complete' || status === 'ready';
}

function isStepActive(status: string): boolean {
    return (
        status === 'processing' ||
        status === 'in_progress' ||
        status === 'running'
    );
}

function isStepFailed(status: string): boolean {
    return status === 'failed';
}

function StepStatusIcon({ status }: { status: string }) {
    if (isStepFailed(status)) {
        return <XCircle className="text-destructive size-4 shrink-0" />;
    }

    if (isStepComplete(status)) {
        return <CheckCircle2 className="text-primary size-4 shrink-0" />;
    }

    if (isStepActive(status)) {
        return <Loader2 className="text-primary size-4 shrink-0 animate-spin" />;
    }

    return <Circle className="text-muted-foreground size-4 shrink-0" />;
}

function ProcessingSteps({
    stepStatuses,
}: {
    stepStatuses: Record<string, string>;
}) {
    return (
        <ul className="space-y-2">
            {Object.entries(stepStatuses).map(([step, status]) => (
                <li
                    key={step}
                    className="flex items-center justify-between gap-4 text-sm"
                >
                    <div className="flex items-center gap-2 capitalize">
                        <StepStatusIcon status={status} />
                        <span>{formatStepLabel(step)}</span>
                    </div>
                    <span className="text-muted-foreground capitalize">
                        {status.replace(/_/g, ' ')}
                    </span>
                </li>
            ))}
        </ul>
    );
}

export default function AudiosIndex({
    uploads,
    processingUploads,
}: AudiosIndexProps) {
    const [activeTab, setActiveTab] = useState<AudiosTab>('ready');
    const [isLoadingProcessing, setIsLoadingProcessing] = useState(false);
    const previousProcessingCountRef = useRef<number | null>(null);
    const [selectedUuid, setSelectedUuid] = useState<string | null>(
        uploads.data[0]?.uuid ?? null,
    );

    const selectedUpload =
        uploads.data.find((upload) => upload.uuid === selectedUuid) ??
        uploads.data[0] ??
        null;

    const { start: startPolling, stop: stopPolling } = usePoll(
        STATUS_POLL_INTERVAL_MS,
        () => ({
            only: ['processingUploads'],
        }),
        {
            autoStart: false,
        },
    );

    useEffect(() => {
        if (activeTab !== 'processing') {
            stopPolling();

            return;
        }

        startPolling();

        return () => {
            stopPolling();
        };
    }, [activeTab, startPolling, stopPolling]);

    useEffect(() => {
        const count = processingUploads?.length ?? 0;

        if (
            previousProcessingCountRef.current !== null &&
            previousProcessingCountRef.current > 0 &&
            count === 0 &&
            activeTab === 'processing'
        ) {
            router.reload({ only: ['uploads'] });
        }

        previousProcessingCountRef.current = count;
    }, [processingUploads, activeTab]);

    function handleTabChange(value: string) {
        if (value !== 'ready' && value !== 'processing') {
            return;
        }

        setActiveTab(value);

        if (value === 'processing' && processingUploads === undefined) {
            setIsLoadingProcessing(true);

            router.reload({
                only: ['processingUploads'],
                onFinish: () => {
                    setIsLoadingProcessing(false);
                },
            });
        }
    }

    return (
        <>
            <Head title="Audios" />

            <div className="flex min-h-0 flex-1 flex-col">
                <div className="flex flex-1 flex-col gap-4 overflow-x-auto overflow-y-auto p-4">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <ToggleGroup
                            type="single"
                            value={activeTab}
                            onValueChange={handleTabChange}
                            variant="outline"
                            className="w-fit"
                        >
                            <ToggleGroupItem value="ready" aria-label="Ready audios">
                                Ready
                            </ToggleGroupItem>
                            <ToggleGroupItem
                                value="processing"
                                aria-label="Processing audios"
                            >
                                Processing
                            </ToggleGroupItem>
                        </ToggleGroup>

                        <Button asChild size="sm" className="w-fit">
                            <Link href={uploadsCreate()} prefetch>
                                <Upload />
                                Upload
                            </Link>
                        </Button>
                    </div>

                    {activeTab === 'ready' ? (
                        uploads.data.length === 0 ? (
                            <Card>
                                <CardHeader className="items-center text-center">
                                    <div className="bg-muted mb-2 flex size-12 items-center justify-center rounded-full">
                                        <Music2 className="text-muted-foreground size-6" />
                                    </div>
                                    <CardTitle>No ready audios yet</CardTitle>
                                    <CardDescription>
                                        Upload an audio file and wait for
                                        processing to finish.
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
                            <Card>
                                <CardHeader>
                                    <CardTitle>Your audios</CardTitle>
                                    <CardDescription>
                                        {uploads.meta.total} ready{' '}
                                        {uploads.meta.total === 1
                                            ? 'upload'
                                            : 'uploads'}
                                    </CardDescription>
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
                                                    {uploads.data.map(
                                                        (upload) => {
                                                            const isSelected =
                                                                upload.uuid ===
                                                                selectedUpload?.uuid;

                                                            return (
                                                                <tr
                                                                    key={
                                                                        upload.uuid
                                                                    }
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
                                                                        {upload
                                                                            .metadata
                                                                            ?.artist ??
                                                                            '—'}
                                                                    </td>
                                                                    <td className="text-muted-foreground px-4 py-3">
                                                                        {upload
                                                                            .metadata
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
                                                        },
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    </InfiniteScroll>
                                </CardContent>
                            </Card>
                        )
                    ) : isLoadingProcessing && processingUploads === undefined ? (
                        <Card>
                            <CardContent className="flex items-center justify-center gap-3 py-12">
                                <Spinner className="size-5" />
                                <span className="text-muted-foreground text-sm">
                                    Loading processing uploads...
                                </span>
                            </CardContent>
                        </Card>
                    ) : (processingUploads?.length ?? 0) === 0 ? (
                        <Card>
                            <CardHeader className="items-center text-center">
                                <CardTitle>No uploads processing</CardTitle>
                                <CardDescription>
                                    Uploads currently being processed will
                                    appear here.
                                </CardDescription>
                            </CardHeader>
                        </Card>
                    ) : (
                        <Card>
                            <CardHeader>
                                <CardTitle>Processing uploads</CardTitle>
                                <CardDescription>
                                    Track progress for uploads still being
                                    prepared.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                {processingUploads?.map((upload) => (
                                    <div
                                        key={upload.uuid}
                                        className="rounded-xl border bg-card/60 p-4"
                                    >
                                        <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p className="font-medium">
                                                    {upload.original_name}
                                                </p>
                                                <p className="text-muted-foreground text-sm">
                                                    Uploaded{' '}
                                                    {formatUploadedAt(
                                                        upload.uploaded_at,
                                                    )}
                                                </p>
                                            </div>
                                            <Badge
                                                variant={
                                                    upload.status === 'failed'
                                                        ? 'destructive'
                                                        : 'secondary'
                                                }
                                            >
                                                {upload.status}
                                            </Badge>
                                        </div>
                                        <ProcessingSteps
                                            stepStatuses={
                                                upload.step_statuses
                                            }
                                        />
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}
                </div>

                {activeTab === 'ready' && selectedUpload && (
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
