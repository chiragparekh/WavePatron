import { Head } from '@inertiajs/react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard as creatorDashboard } from '@/routes/creator';

export default function CreatorDashboard() {
    return (
        <>
            <Head title="Creator dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Creator dashboard</CardTitle>
                        <CardDescription>
                            Manage your creator profile, publish audio, and
                            configure tiers from here in upcoming phases.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p className="text-muted-foreground text-sm">
                            This is a placeholder while creator tooling is
                            built out.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CreatorDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Creator dashboard',
            href: creatorDashboard(),
        },
    ],
};
