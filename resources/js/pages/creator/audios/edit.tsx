import { Form, Head } from '@inertiajs/react';
import AudioPlayer from '@/components/audio-player';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AudioController from '@/actions/App/Http/Controllers/Creator/AudioController';
import { edit as editCreatorAudio, index as creatorAudios } from '@/routes/creator/audios';
import type { CreatorAudioDetail } from '@/types/upload';

type CreatorAudioEditProps = {
    upload: CreatorAudioDetail;
};

export default function CreatorAudioEdit({ upload }: CreatorAudioEditProps) {
    const canPublish = upload.can_publish;

    return (
        <>
            <Head title={`Manage ${upload.display_title}`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Form
                    {...AudioController.update.form(upload.uuid)}
                    options={{ preserveScroll: true }}
                    className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]"
                >
                    {({ processing, errors }) => (
                        <>
                            <Card>
                                <CardHeader>
                                    <CardTitle>Publishing</CardTitle>
                                    <CardDescription>
                                        Set how listeners discover and access
                                        this audio.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex flex-wrap gap-2">
                                        <Badge variant="outline">
                                            {upload.status.replace(/_/g, ' ')}
                                        </Badge>
                                        {!canPublish && (
                                            <Badge variant="secondary">
                                                Processing must finish before
                                                publishing
                                            </Badge>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="title">Title</Label>
                                        <Input
                                            id="title"
                                            name="title"
                                            defaultValue={upload.title ?? ''}
                                            placeholder={upload.original_name}
                                        />
                                        <InputError message={errors.title} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="description">
                                            Description
                                        </Label>
                                        <textarea
                                            id="description"
                                            name="description"
                                            defaultValue={
                                                upload.description ?? ''
                                            }
                                            rows={4}
                                            className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="publish_status">
                                            Publish status
                                        </Label>
                                        <select
                                            id="publish_status"
                                            name="publish_status"
                                            defaultValue={upload.publish_status}
                                            disabled={!canPublish}
                                            className="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <option value="draft">Draft</option>
                                            <option value="published">
                                                Published
                                            </option>
                                        </select>
                                        <InputError
                                            message={errors.publish_status}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="access_level">
                                            Access level
                                        </Label>
                                        <select
                                            id="access_level"
                                            name="access_level"
                                            defaultValue={upload.access_level}
                                            className="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                        >
                                            <option value="free">Free</option>
                                            <option value="premium">
                                                Premium
                                            </option>
                                        </select>
                                        <InputError
                                            message={errors.access_level}
                                        />
                                    </div>

                                    <Button disabled={processing} type="submit">
                                        Save changes
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Preview</CardTitle>
                                    <CardDescription>
                                        Playback is available once processing
                                        completes.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {upload.hls_playlist_url &&
                                    upload.waveform_url ? (
                                        <AudioPlayer
                                            upload={{
                                                uuid: upload.uuid,
                                                original_name:
                                                    upload.original_name,
                                                uploaded_at: upload.uploaded_at,
                                                metadata: upload.metadata
                                                    ? {
                                                          title:
                                                              upload.metadata
                                                                  .title,
                                                          artist:
                                                              upload.metadata
                                                                  .artist,
                                                          duration:
                                                              upload.metadata
                                                                  .duration,
                                                          duration_seconds:
                                                              null,
                                                          codec: null,
                                                          bitrate: null,
                                                          sample_rate: null,
                                                      }
                                                    : null,
                                                hls_playlist_url:
                                                    upload.hls_playlist_url,
                                                waveform_url:
                                                    upload.waveform_url,
                                            }}
                                        />
                                    ) : (
                                        <p className="text-muted-foreground text-sm">
                                            Preview unavailable while this upload
                                            is still processing.
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CreatorAudioEdit.layout = {
    breadcrumbs: [
        {
            title: 'Creator audio',
            href: creatorAudios(),
        },
        {
            title: 'Manage audio',
            href: creatorAudios(),
        },
    ],
};
