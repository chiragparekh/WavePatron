import { Head, Link } from '@inertiajs/react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index as creatorAudios } from '@/routes/creator/audios';
import { index as creatorTiers } from '@/routes/creator/tiers';
import { edit as editCreatorProfile } from '@/routes/creator/profile';
import { dashboard } from '@/routes';
import { create as uploadsCreate } from '@/routes/uploads';

export default function CreatorDashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Dashboard</CardTitle>
                        <CardDescription>
                            Manage your creator profile, publish audio, and
                            configure support tiers.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p className="text-muted-foreground text-sm">
                            Manage your public profile, publish audio, and request
                            paid tiers for admin approval.
                        </p>
                        <div className="mt-4 flex flex-col gap-2 text-sm">
                            <Link
                                href={editCreatorProfile()}
                                className="text-primary font-medium underline"
                            >
                                Edit creator profile
                            </Link>
                            <Link
                                href={creatorAudios()}
                                className="text-primary font-medium underline"
                            >
                                Manage creator audio
                            </Link>
                            <Link
                                href="/creator/payouts"
                                className="text-primary font-medium underline"
                            >
                                Payout setup
                            </Link>
                            <Link
                                href={creatorTiers()}
                                className="text-primary font-medium underline"
                            >
                                Manage support tiers
                            </Link>
                            <Link
                                href={uploadsCreate()}
                                className="text-primary font-medium underline"
                            >
                                Upload new audio
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CreatorDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
