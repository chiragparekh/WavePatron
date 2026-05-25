import { Form, Head } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Creator/ProfileController';
import CreatorProfileFormFields from '@/components/creator-profile-form-fields';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { edit } from '@/routes/creator/profile';
import type { CreatorProfileFormData } from '@/types/creator-profile';

export default function EditCreatorProfile({
    profile,
}: {
    profile: CreatorProfileFormData;
}) {
    return (
        <>
            <Head title="Creator profile" />
            <div className="mx-auto flex h-full w-full max-w-2xl flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Creator profile</CardTitle>
                        <CardDescription>
                            Update how listeners see your public creator page.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...ProfileController.update.form()}
                            options={{
                                preserveScroll: true,
                            }}
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <CreatorProfileFormFields
                                        defaults={profile}
                                        errors={errors}
                                    />
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                    >
                                        Save changes
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

EditCreatorProfile.layout = {
    breadcrumbs: [
        {
            title: 'Creator profile',
            href: edit(),
        },
    ],
};
