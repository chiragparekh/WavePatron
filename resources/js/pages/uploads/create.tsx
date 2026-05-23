import { Head } from '@inertiajs/react';
import AudioUploader from '@/components/audio-uploader';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { create as uploadsCreate } from '@/routes/uploads';

export default function UploadsCreate() {
    return (
        <>
            <Head title="Upload audio" />
            <div className="relative flex min-h-0 flex-1 flex-col overflow-hidden">
                <PlaceholderPattern className="text-foreground/[0.03] absolute inset-0 size-full stroke-current" />
                <div className="relative flex flex-1 items-center justify-center p-6 sm:p-10">
                    <AudioUploader />
                </div>
            </div>
        </>
    );
}

UploadsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Upload',
            href: uploadsCreate(),
        },
    ],
};
