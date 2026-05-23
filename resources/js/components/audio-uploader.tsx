import { Link, useHttp } from '@inertiajs/react';
import {
    CheckCircle2,
    Circle,
    FileAudio,
    Loader2,
    Music2,
    Upload,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { show, store, update } from '@/actions/App/Http/Controllers/Api/UploadController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { index as audios } from '@/routes/audios';

const MAX_UPLOAD_BYTES = 500 * 1024 * 1024;
const STATUS_POLL_INTERVAL_MS = 2000;

type SignedUploadResponse = {
    uuid: string;
    url: string;
    headers: Record<string, string>;
    path: string;
    expires_at: string;
};

type UploadResponse = {
    uuid: string;
    status: string;
    step_statuses: Record<string, string>;
    path: string;
    original_name: string;
    uploaded_at: string | null;
};

type UploadPhase =
    | 'idle'
    | 'signing'
    | 'uploading'
    | 'confirming'
    | 'processing'
    | 'success'
    | 'error';

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function formatStepLabel(step: string): string {
    return step.replace(/_/g, ' ');
}

function isPollingStatus(status: string): boolean {
    return status === 'uploaded' || status === 'processing';
}

function isStepComplete(status: string): boolean {
    return status === 'completed' || status === 'complete' || status === 'ready';
}

function isStepActive(status: string): boolean {
    return status === 'processing' || status === 'in_progress' || status === 'running';
}

function uploadFileToStorage(
    file: File,
    signed: SignedUploadResponse,
    onProgress: (percent: number) => void,
): Promise<void> {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable) {
                return;
            }

            onProgress(Math.round((event.loaded / event.total) * 100));
        });

        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                resolve();

                return;
            }

            reject(new Error(`Upload failed with status ${xhr.status}.`));
        });

        xhr.addEventListener('error', () => {
            reject(new Error('Upload failed due to a network error.'));
        });

        xhr.addEventListener('abort', () => {
            reject(new Error('Upload was cancelled.'));
        });

        xhr.open('PUT', signed.url);

        Object.entries(signed.headers).forEach(([key, value]) => {
            xhr.setRequestHeader(key, value);
        });

        xhr.send(file);
    });
}

function validateAudioFile(file: File): string | null {
    if (!file.type.startsWith('audio/')) {
        return 'Please choose an audio file.';
    }

    if (file.size > MAX_UPLOAD_BYTES) {
        return 'Files must be 500 MB or smaller.';
    }

    return null;
}

export default function AudioUploader() {
    const inputRef = useRef<HTMLInputElement>(null);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [phase, setPhase] = useState<UploadPhase>('idle');
    const [uploadProgress, setUploadProgress] = useState(0);
    const [clientError, setClientError] = useState<string | null>(null);
    const [uploadError, setUploadError] = useState<string | null>(null);
    const [isDragOver, setIsDragOver] = useState(false);
    const [uploadedUpload, setUploadedUpload] = useState<UploadResponse | null>(
        null,
    );

    const { setData, post, processing, errors, reset } = useHttp<
        { name: string; size: number; type: string },
        SignedUploadResponse
    >({
        name: '',
        size: 0,
        type: '',
    });

    const { patch: confirmUpload, processing: confirming } = useHttp<
        Record<string, never>,
        UploadResponse
    >({});

    const { get: fetchUploadStatus } = useHttp<
        Record<string, never>,
        UploadResponse
    >({});

    function resetState(): void {
        setPhase('idle');
        setUploadProgress(0);
        setClientError(null);
        setUploadError(null);
        setUploadedUpload(null);
        reset();
    }

    function selectFile(file: File | null): void {
        resetState();
        setSelectedFile(null);

        if (!file) {
            return;
        }

        const fileValidationError = validateAudioFile(file);

        if (fileValidationError) {
            setClientError(fileValidationError);

            return;
        }

        setSelectedFile(file);
        setData({
            name: file.name,
            size: file.size,
            type: file.type,
        });
    }

    useEffect(() => {
        const uploadUuid = uploadedUpload?.uuid;

        if (phase !== 'processing' || uploadUuid === undefined) {
            return;
        }

        let cancelled = false;

        async function pollUploadStatus(uuid: string): Promise<void> {
            try {
                const upload = await fetchUploadStatus(show.url(uuid));

                if (cancelled) {
                    return;
                }

                setUploadedUpload(upload);

                if (upload.status === 'ready') {
                    setPhase('success');

                    return;
                }

                if (upload.status === 'failed') {
                    setPhase('error');
                    setUploadError('Processing failed. Please try again.');

                    return;
                }

                if (!isPollingStatus(upload.status)) {
                    setPhase('error');
                    setUploadError(
                        `Unexpected upload status: ${upload.status}.`,
                    );
                }
            } catch {
                if (!cancelled) {
                    setPhase('error');
                    setUploadError(
                        'Unable to check processing status. Please try again.',
                    );
                }
            }
        }

        void pollUploadStatus(uploadUuid);

        const intervalId = window.setInterval(() => {
            void pollUploadStatus(uploadUuid);
        }, STATUS_POLL_INTERVAL_MS);

        return () => {
            cancelled = true;
            window.clearInterval(intervalId);
        };
    }, [fetchUploadStatus, phase, uploadedUpload?.uuid]);

    function handleFileChange(event: React.ChangeEvent<HTMLInputElement>): void {
        selectFile(event.target.files?.[0] ?? null);
    }

    const isUploadBusy =
        processing ||
        confirming ||
        phase === 'uploading' ||
        phase === 'signing' ||
        phase === 'confirming';

    function handleDragOver(event: React.DragEvent<HTMLDivElement>): void {
        event.preventDefault();
        setIsDragOver(true);
    }

    function handleDragLeave(event: React.DragEvent<HTMLDivElement>): void {
        event.preventDefault();
        setIsDragOver(false);
    }

    function handleDrop(event: React.DragEvent<HTMLDivElement>): void {
        event.preventDefault();
        setIsDragOver(false);

        if (isUploadBusy) {
            return;
        }

        selectFile(event.dataTransfer.files?.[0] ?? null);
    }

    function clearSelectedFile(): void {
        if (inputRef.current) {
            inputRef.current.value = '';
        }

        setSelectedFile(null);
        resetState();
    }

    async function handleUpload(): Promise<void> {
        if (!selectedFile) {
            setClientError('Choose an audio file before uploading.');

            return;
        }

        setClientError(null);
        setUploadError(null);
        setUploadedUpload(null);
        setUploadProgress(0);
        setPhase('signing');

        try {
            const signed = await post(store.url());

            setPhase('uploading');

            await uploadFileToStorage(selectedFile, signed, setUploadProgress);

            setPhase('confirming');

            const upload = await confirmUpload(update.url(signed.uuid));

            setUploadedUpload(upload);
            setPhase('processing');
        } catch (error) {
            setPhase('error');
            setUploadError(
                error instanceof Error
                    ? error.message
                    : 'Something went wrong while uploading.',
            );
        }
    }

    const showDropZone =
        phase === 'idle' ||
        phase === 'signing' ||
        phase === 'uploading' ||
        phase === 'confirming' ||
        phase === 'error';
    const validationError =
        errors.name ?? errors.size ?? errors.type;
    const statusMessage =
        phase === 'signing'
            ? 'Requesting upload URL...'
            : phase === 'confirming'
              ? 'Confirming upload...'
              : phase === 'uploading'
                ? 'Uploading to storage...'
                : null;

    return (
        <div className="mx-auto w-full max-w-2xl space-y-8">
            <div className="space-y-2 text-center">
                <div className="bg-primary/10 text-primary mx-auto flex size-14 items-center justify-center rounded-2xl">
                    <Music2 className="size-7" />
                </div>
                <h1 className="text-3xl font-semibold tracking-tight">
                    Upload audio
                </h1>
                <p className="text-muted-foreground mx-auto max-w-md text-sm leading-relaxed">
                    Drag and drop your file or browse from your device. Files
                    upload directly to storage — up to 500 MB.
                </p>
            </div>

            {showDropZone && (
                <div className="space-y-4">
                    <div
                        role="button"
                        tabIndex={0}
                        onClick={() => {
                            if (!isUploadBusy) {
                                inputRef.current?.click();
                            }
                        }}
                        onKeyDown={(event) => {
                            if (
                                (event.key === 'Enter' || event.key === ' ') &&
                                !isUploadBusy
                            ) {
                                event.preventDefault();
                                inputRef.current?.click();
                            }
                        }}
                        onDragOver={handleDragOver}
                        onDragLeave={handleDragLeave}
                        onDrop={handleDrop}
                        className={cn(
                            'group relative flex min-h-56 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-10 transition-all outline-none focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                            isDragOver
                                ? 'border-primary bg-primary/5 scale-[1.01]'
                                : 'border-border/80 bg-card/50 hover:border-primary/40 hover:bg-card/80',
                            isUploadBusy && 'pointer-events-none opacity-70',
                        )}
                    >
                        <input
                            ref={inputRef}
                            id="audio"
                            type="file"
                            accept="audio/*"
                            className="sr-only"
                            onChange={handleFileChange}
                            disabled={isUploadBusy}
                        />

                        {selectedFile ? (
                            <div className="flex w-full max-w-md flex-col items-center gap-4">
                                <div className="bg-primary/10 text-primary flex size-16 items-center justify-center rounded-2xl">
                                    <FileAudio className="size-8" />
                                </div>
                                <div className="min-w-0 text-center">
                                    <p className="truncate text-base font-medium">
                                        {selectedFile.name}
                                    </p>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        {formatBytes(selectedFile.size)}
                                        {selectedFile.type
                                            ? ` · ${selectedFile.type}`
                                            : ''}
                                    </p>
                                </div>
                                {!isUploadBusy && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="text-muted-foreground"
                                        onClick={(event) => {
                                            event.stopPropagation();
                                            clearSelectedFile();
                                        }}
                                    >
                                        <X />
                                        Choose a different file
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <>
                                <div className="bg-muted group-hover:bg-primary/10 mb-4 flex size-16 items-center justify-center rounded-2xl transition-colors">
                                    <Upload className="text-muted-foreground group-hover:text-primary size-7 transition-colors" />
                                </div>
                                <p className="text-base font-medium">
                                    Drop your audio file here
                                </p>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    or click to browse
                                </p>
                                <p className="text-muted-foreground/70 mt-4 text-xs">
                                    MP3, WAV, FLAC, AAC and more
                                </p>
                            </>
                        )}
                    </div>

                    {(phase === 'signing' ||
                        phase === 'uploading' ||
                        phase === 'confirming' ||
                        uploadProgress > 0) && (
                        <div className="space-y-2 rounded-xl border bg-card/60 p-4">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">
                                    {statusMessage}
                                </span>
                                {phase === 'uploading' && (
                                    <span className="font-medium tabular-nums">
                                        {uploadProgress}%
                                    </span>
                                )}
                            </div>
                            <div className="bg-muted h-1.5 overflow-hidden rounded-full">
                                <div
                                    className={cn(
                                        'bg-primary h-full transition-all duration-200',
                                        (phase === 'signing' ||
                                            phase === 'confirming') &&
                                            'w-1/3 animate-pulse',
                                    )}
                                    style={{
                                        width:
                                            phase === 'signing' ||
                                            phase === 'confirming'
                                                ? undefined
                                                : `${uploadProgress}%`,
                                    }}
                                />
                            </div>
                        </div>
                    )}

                    <div className="flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <Button
                            type="button"
                            size="lg"
                            className="min-w-40"
                            onClick={() => void handleUpload()}
                            disabled={!selectedFile || isUploadBusy}
                        >
                            {isUploadBusy ? (
                                <>
                                    <Spinner />
                                    Uploading...
                                </>
                            ) : (
                                <>
                                    <Upload />
                                    Upload audio
                                </>
                            )}
                        </Button>
                        {selectedFile && !isUploadBusy && (
                            <Button
                                type="button"
                                variant="outline"
                                size="lg"
                                onClick={clearSelectedFile}
                            >
                                Clear
                            </Button>
                        )}
                    </div>
                </div>
            )}

            {phase === 'processing' && uploadedUpload && (
                <div className="space-y-4 rounded-2xl border bg-card/60 p-6">
                    <div className="flex items-center gap-3">
                        <Spinner className="size-5" />
                        <div>
                            <p className="font-medium">Processing your audio</p>
                            <p className="text-muted-foreground text-sm">
                                Preparing metadata, waveform, and streaming
                                output.
                            </p>
                        </div>
                    </div>
                    <ul className="space-y-3">
                        {Object.entries(uploadedUpload.step_statuses).map(
                            ([step, status]) => {
                                const complete = isStepComplete(status);
                                const active = isStepActive(status);

                                return (
                                    <li
                                        key={step}
                                        className="flex items-center justify-between gap-4 text-sm"
                                    >
                                        <div className="flex items-center gap-3">
                                            {complete ? (
                                                <CheckCircle2 className="text-primary size-4 shrink-0" />
                                            ) : active ? (
                                                <Loader2 className="text-primary size-4 shrink-0 animate-spin" />
                                            ) : (
                                                <Circle className="text-muted-foreground/50 size-4 shrink-0" />
                                            )}
                                            <span className="capitalize">
                                                {formatStepLabel(step)}
                                            </span>
                                        </div>
                                        <span
                                            className={cn(
                                                'text-xs capitalize',
                                                complete
                                                    ? 'text-primary'
                                                    : 'text-muted-foreground',
                                            )}
                                        >
                                            {status.replace(/_/g, ' ')}
                                        </span>
                                    </li>
                                );
                            },
                        )}
                    </ul>
                </div>
            )}

            {phase === 'success' && uploadedUpload && (
                <div className="space-y-6 rounded-2xl border bg-card/60 p-8 text-center">
                    <div className="bg-primary/10 text-primary mx-auto flex size-16 items-center justify-center rounded-full">
                        <CheckCircle2 className="size-8" />
                    </div>
                    <div className="space-y-2">
                        <h2 className="text-xl font-semibold">
                            Upload complete
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            {uploadedUpload.original_name} is ready to play.
                        </p>
                    </div>
                    <div className="flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <Button asChild size="lg">
                            <Link href={audios()} prefetch>
                                <Music2 />
                                Go to audios
                            </Link>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="lg"
                            onClick={clearSelectedFile}
                        >
                            Upload another
                        </Button>
                    </div>
                </div>
            )}

            {(clientError || validationError || uploadError) && (
                <div className="rounded-xl border border-destructive/30 bg-destructive/5 px-4 py-3">
                    {clientError && (
                        <InputError message={clientError} className="mt-0" />
                    )}
                    {validationError && (
                        <InputError message={validationError} className="mt-0" />
                    )}
                    {uploadError && (
                        <InputError message={uploadError} className="mt-0" />
                    )}
                </div>
            )}
        </div>
    );
}
