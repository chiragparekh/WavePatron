import { Head, Link } from '@inertiajs/react';
import { Music2, Pencil } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index as creatorAudios, edit as editCreatorAudio } from '@/routes/creator/audios';
import { create as uploadsCreate } from '@/routes/uploads';
import type { CreatorAudioListItem, Paginated } from '@/types/upload';

type CreatorAudiosIndexProps = {
    uploads: Paginated<CreatorAudioListItem>;
};

function statusLabel(status: string): string {
    return status.replace(/_/g, ' ');
}

export default function CreatorAudiosIndex({ uploads }: CreatorAudiosIndexProps) {
    return (
        <>
            <Head title="Creator audio" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader className="flex flex-row items-start justify-between gap-4">
                        <div>
                            <CardTitle>Your audio</CardTitle>
                            <CardDescription>
                                Manage titles, publishing, and access for your
                                uploads.
                            </CardDescription>
                        </div>
                        <Button asChild>
                            <Link href={uploadsCreate()} prefetch>
                                Upload audio
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {uploads.data.length === 0 ? (
                            <div className="flex flex-col items-center gap-3 py-10 text-center">
                                <Music2 className="text-muted-foreground size-10" />
                                <p className="text-muted-foreground text-sm">
                                    No uploads yet. Upload audio to get started.
                                </p>
                                <Button asChild variant="outline">
                                    <Link href={uploadsCreate()} prefetch>
                                        Upload audio
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            uploads.data.map((upload) => (
                                <div
                                    key={upload.uuid}
                                    className="flex flex-col gap-3 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="space-y-2">
                                        <p className="font-medium">
                                            {upload.display_title}
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            <Badge variant="outline">
                                                {statusLabel(upload.status)}
                                            </Badge>
                                            <Badge
                                                variant={
                                                    upload.publish_status ===
                                                    'published'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {upload.publish_status}
                                            </Badge>
                                            <Badge variant="outline">
                                                {upload.access_level}
                                            </Badge>
                                        </div>
                                    </div>
                                    <Button asChild size="sm" variant="outline">
                                        <Link
                                            href={editCreatorAudio(upload.uuid)}
                                            prefetch
                                        >
                                            <Pencil className="size-4" />
                                            Manage
                                        </Link>
                                    </Button>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CreatorAudiosIndex.layout = {
    breadcrumbs: [
        {
            title: 'Creator audio',
            href: creatorAudios(),
        },
    ],
};
