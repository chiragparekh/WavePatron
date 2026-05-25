import { Head, Link } from '@inertiajs/react';
import {
    HardDrive,
    Loader2,
    Music2,
    Upload,
    XCircle,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as audios } from '@/routes/audios';
import { create as uploadsCreate } from '@/routes/uploads';
import type { UploadStats } from '@/types/upload';

type DashboardProps = {
    stats: UploadStats;
};

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    if (bytes < 1024 * 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    return `${(bytes / (1024 * 1024 * 1024)).toFixed(1)} GB`;
}

function StatCard({
    title,
    value,
    icon: Icon,
    iconClassName,
}: {
    title: string;
    value: string | number;
    icon: typeof Music2;
    iconClassName?: string;
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                <Icon className={cn('text-muted-foreground size-4', iconClassName)} />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold tabular-nums">{value}</div>
            </CardContent>
        </Card>
    );
}

export default function ListenerDashboard({ stats }: DashboardProps) {
    return (
        <>
            <Head title="Listener dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Ready audios"
                        value={stats.total_ready}
                        icon={Music2}
                    />
                    <StatCard
                        title="Processing"
                        value={stats.total_processing}
                        icon={Loader2}
                        iconClassName={
                            stats.total_processing > 0 ? 'animate-spin' : undefined
                        }
                    />
                    <StatCard
                        title="Failed"
                        value={stats.total_failed}
                        icon={XCircle}
                    />
                    <StatCard
                        title="Storage used"
                        value={formatBytes(stats.total_storage_bytes)}
                        icon={HardDrive}
                    />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Upload audio</CardTitle>
                            <CardDescription>
                                Add a new audio file and track processing
                                progress.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button asChild>
                                <Link href={uploadsCreate()} prefetch>
                                    <Upload />
                                    Upload audio
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Browse audios</CardTitle>
                            <CardDescription>
                                Listen to processed uploads and review metadata.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button asChild variant="outline">
                                <Link href={audios()} prefetch>
                                    <Music2 />
                                    View audios
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
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
