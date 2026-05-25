import { Head, Link } from '@inertiajs/react';
import { Music2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as audios } from '@/routes/audios';

export default function ListenerDashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Browse audios</CardTitle>
                        <CardDescription>
                            Listen to free and subscribed premium audio from
                            creators across the platform.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button asChild>
                            <Link href={audios()} prefetch>
                                <Music2 />
                                View audio library
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ListenerDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
