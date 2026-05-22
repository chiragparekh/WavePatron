import { useHttp } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { store, update } from '@/actions/App/Http/Controllers/UploadController';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

const MAX_UPLOAD_BYTES = 500 * 1024 * 1024;

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

export default function AudioUploader() {
    const inputRef = useRef<HTMLInputElement>(null);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [phase, setPhase] = useState<UploadPhase>('idle');
    const [uploadProgress, setUploadProgress] = useState(0);
    const [clientError, setClientError] = useState<string | null>(null);
    const [uploadError, setUploadError] = useState<string | null>(null);
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

    function resetState(): void {
        setPhase('idle');
        setUploadProgress(0);
        setClientError(null);
        setUploadError(null);
        setUploadedUpload(null);
        reset();
    }

    function handleFileChange(event: React.ChangeEvent<HTMLInputElement>): void {
        const file = event.target.files?.[0] ?? null;

        resetState();
        setSelectedFile(file);

        if (!file) {
            return;
        }

        if (!file.type.startsWith('audio/')) {
            setClientError('Please choose an audio file.');
            setSelectedFile(null);
            event.target.value = '';

            return;
        }

        if (file.size > MAX_UPLOAD_BYTES) {
            setClientError('Files must be 500 MB or smaller.');
            setSelectedFile(null);
            event.target.value = '';

            return;
        }

        setData({
            name: file.name,
            size: file.size,
            type: file.type,
        });
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
            setPhase('success');
        } catch (error) {
            setPhase('error');
            setUploadError(
                error instanceof Error
                    ? error.message
                    : 'Something went wrong while uploading.',
            );
        }
    }

    const isBusy =
        processing ||
        confirming ||
        phase === 'uploading' ||
        phase === 'signing' ||
        phase === 'confirming';
    const validationError =
        errors.name ?? errors.size ?? errors.type;

    return (
        <Card>
            <CardHeader>
                <CardTitle>Upload audio</CardTitle>
                <CardDescription>
                    Files upload directly to storage. Maximum size is 500 MB.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <div className="grid gap-2">
                    <Label htmlFor="audio">Audio file</Label>
                    <input
                        ref={inputRef}
                        id="audio"
                        type="file"
                        accept="audio/*"
                        className="block w-full text-sm file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground hover:file:bg-primary/90"
                        onChange={handleFileChange}
                        disabled={isBusy}
                    />
                </div>

                {selectedFile && (
                    <dl className="grid gap-2 rounded-lg border p-4 text-sm">
                        <div className="flex justify-between gap-4">
                            <dt className="text-muted-foreground">Name</dt>
                            <dd className="text-right font-medium">
                                {selectedFile.name}
                            </dd>
                        </div>
                        <div className="flex justify-between gap-4">
                            <dt className="text-muted-foreground">Size</dt>
                            <dd className="font-medium">
                                {formatBytes(selectedFile.size)}
                            </dd>
                        </div>
                        <div className="flex justify-between gap-4">
                            <dt className="text-muted-foreground">MIME type</dt>
                            <dd className="font-mono text-xs">
                                {selectedFile.type || 'unknown'}
                            </dd>
                        </div>
                    </dl>
                )}

                {(phase === 'signing' ||
                    phase === 'uploading' ||
                    phase === 'confirming' ||
                    uploadProgress > 0) && (
                    <div className="space-y-2">
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-muted-foreground">
                                {phase === 'signing'
                                    ? 'Requesting upload URL...'
                                    : phase === 'confirming'
                                      ? 'Confirming upload...'
                                      : 'Uploading to storage...'}
                            </span>
                            <span>
                                {phase === 'signing' || phase === 'confirming'
                                    ? null
                                    : `${uploadProgress}%`}
                            </span>
                        </div>
                        <div className="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                className={cn(
                                    'h-full bg-primary transition-all duration-200',
                                    (phase === 'signing' ||
                                        phase === 'confirming') &&
                                        'w-1/4 animate-pulse',
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

                {phase === 'success' && uploadedUpload && (
                    <Alert>
                        <AlertTitle>Upload complete</AlertTitle>
                        <AlertDescription className="space-y-1">
                            <p>Your audio file was uploaded successfully.</p>
                            <p className="font-mono text-xs break-all">
                                {uploadedUpload.uuid}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Status: {uploadedUpload.status}
                            </p>
                        </AlertDescription>
                    </Alert>
                )}

                {clientError && (
                    <InputError message={clientError} className="mt-0" />
                )}

                {validationError && (
                    <InputError message={validationError} className="mt-0" />
                )}

                {uploadError && (
                    <InputError message={uploadError} className="mt-0" />
                )}

                <div className="flex flex-wrap gap-3">
                    <Button
                        type="button"
                        onClick={handleUpload}
                        disabled={!selectedFile || isBusy}
                    >
                        {isBusy && <Spinner />}
                        Upload
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => {
                            if (inputRef.current) {
                                inputRef.current.value = '';
                            }

                            setSelectedFile(null);
                            resetState();
                        }}
                        disabled={isBusy && phase !== 'error'}
                    >
                        Reset
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
