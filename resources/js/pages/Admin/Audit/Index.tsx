import { Head, router } from '@inertiajs/react';
import { format } from 'date-fns';
import AppLayout from '@/layouts/app-layout';
import { Pagination } from '@/components/ui/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useState } from 'react';

interface Causer {
    id: number;
    name: string;
    email: string;
}

interface ActivityRow {
    id: number;
    log_name: string | null;
    description: string;
    event: string | null;
    subject_type: string | null;
    subject_id: string | number | null;
    causer: Causer | null;
    created_at: string;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    activities: Paginated<ActivityRow>;
    filters: {
        log_name: string;
        event: string;
    };
}

function shortClass(type: string | null): string {
    if (!type) {
        return '—';
    }
    const parts = type.split('\\');
    return parts[parts.length - 1] ?? type;
}

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Audit log' },
];

export default function AuditIndex({ activities, filters }: Props) {
    const [logName, setLogName] = useState(filters.log_name || '');
    const [event, setEvent] = useState(filters.event || '');

    const applyFilters = () => {
        router.get(
            route('admin.audit-log.index'),
            { log_name: logName || undefined, event: event || undefined },
            { preserveState: true }
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit log" />

            <div className="space-y-6 px-4 py-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Audit log</h1>
                    <p className="text-muted-foreground text-sm">
                        Login, logout, and content changes recorded for compliance review.
                    </p>
                </div>

                <div className="flex flex-col gap-4 rounded-lg border p-4 md:flex-row md:items-end">
                    <div className="grid gap-2 md:w-48">
                        <Label htmlFor="filter-log">Log name</Label>
                        <Input
                            id="filter-log"
                            placeholder="auth, post, page…"
                            value={logName}
                            onChange={(e) => setLogName(e.target.value)}
                        />
                    </div>
                    <div className="grid gap-2 md:w-48">
                        <Label htmlFor="filter-event">Event</Label>
                        <Input
                            id="filter-event"
                            placeholder="created, updated…"
                            value={event}
                            onChange={(e) => setEvent(e.target.value)}
                        />
                    </div>
                    <Button type="button" onClick={applyFilters}>
                        Apply filters
                    </Button>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Time</TableHead>
                                <TableHead>Log</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead>Event</TableHead>
                                <TableHead>User</TableHead>
                                <TableHead>Subject</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {activities.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center text-muted-foreground">
                                        No activity found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                activities.data.map((row) => (
                                    <TableRow key={row.id}>
                                        <TableCell className="whitespace-nowrap text-sm">
                                            {format(new Date(row.created_at), 'yyyy-MM-dd HH:mm')}
                                        </TableCell>
                                        <TableCell className="text-sm">{row.log_name ?? '—'}</TableCell>
                                        <TableCell className="max-w-xs truncate text-sm">{row.description}</TableCell>
                                        <TableCell className="text-sm">{row.event ?? '—'}</TableCell>
                                        <TableCell className="text-sm">
                                            {row.causer ? row.causer.name : '—'}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {row.subject_type ? (
                                                <>
                                                    {shortClass(row.subject_type)}
                                                    {row.subject_id != null ? ` #${row.subject_id}` : ''}
                                                </>
                                            ) : (
                                                '—'
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                {activities.last_page > 1 && (
                    <Pagination
                        currentPage={activities.current_page}
                        lastPage={activities.last_page}
                        queryParams={{
                            ...(filters.log_name ? { log_name: filters.log_name } : {}),
                            ...(filters.event ? { event: filters.event } : {}),
                        }}
                    />
                )}
            </div>
        </AppLayout>
    );
}
