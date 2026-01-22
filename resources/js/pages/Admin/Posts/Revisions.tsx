import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ArrowLeft, RotateCcw, Clock } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useState } from 'react';
import { toast } from 'sonner';

interface Revision {
    id: number;
    revision_number: number;
    title: string;
    excerpt: string | null;
    content_preview: string;
    user: string;
    created_at: string;
    created_at_diff: string;
}

interface Props {
    post: {
        id: number;
        title: string;
    };
    revisions: Revision[];
}

export default function Revisions({ post, revisions }: Props) {
    const [restoringId, setRestoringId] = useState<number | null>(null);

    const breadcrumbs = [
        {
            title: 'Dashboard',
            href: route('admin.dashboard'),
        },
        {
            title: 'Posts',
            href: route('admin.posts.index'),
        },
        {
            title: post.title,
            href: route('admin.posts.edit', post.id),
        },
        {
            title: 'Revisions',
        },
    ];

    const handleRestore = (revision: Revision) => {
        setRestoringId(revision.id);
        router.post(
            route('admin.posts.revisions.restore', { post: post.id, revision: revision.id }),
            {},
            {
                onSuccess: () => {
                    toast.success(`Restored to revision #${revision.revision_number}`);
                    setRestoringId(null);
                },
                onError: () => {
                    toast.error('Failed to restore revision');
                    setRestoringId(null);
                },
            }
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Revisions - ${post.title}`} />
            <div className="p-4">
                <div className="flex items-center gap-4 mb-6">
                    <Link href={route('admin.posts.edit', post.id)}>
                        <Button variant="outline" size="sm">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Back to Edit
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold">Revision History</h1>
                        <p className="text-muted-foreground">
                            {revisions.length} revision{revisions.length !== 1 ? 's' : ''} for "{post.title}"
                        </p>
                    </div>
                </div>

                {revisions.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Clock className="h-12 w-12 text-muted-foreground mb-4" />
                            <h3 className="text-lg font-semibold mb-2">No Revisions Yet</h3>
                            <p className="text-muted-foreground text-center">
                                Revisions are automatically created when you update a post.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y,4">
                        {revisions.map((revision) => (
                            <Card key={revision.id} className="mb-4">
                                <CardHeader className="pb-3">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <CardTitle className="text-lg">
                                                Revision #{revision.revision_number}
                                            </CardTitle>
                                            <CardDescription>
                                                {revision.created_at} ({revision.created_at_diff}) by {revision.user}
                                            </CardDescription>
                                        </div>
                                        <Dialog>
                                            <DialogTrigger asChild>
                                                <Button variant="outline" size="sm">
                                                    <RotateCcw className="h-4 w-4 mr-2" />
                                                    Restore
                                                </Button>
                                            </DialogTrigger>
                                            <DialogContent>
                                                <DialogHeader>
                                                    <DialogTitle>Restore Revision</DialogTitle>
                                                    <DialogDescription>
                                                        Are you sure you want to restore to revision #{revision.revision_number}?
                                                        The current content will be saved as a new revision before restoring.
                                                    </DialogDescription>
                                                </DialogHeader>
                                                <DialogFooter>
                                                    <Button
                                                        onClick={() => handleRestore(revision)}
                                                        disabled={restoringId === revision.id}
                                                    >
                                                        {restoringId === revision.id ? 'Restoring...' : 'Restore'}
                                                    </Button>
                                                </DialogFooter>
                                            </DialogContent>
                                        </Dialog>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-2">
                                        <div>
                                            <span className="text-sm font-medium text-muted-foreground">Title:</span>
                                            <p className="font-medium">{revision.title}</p>
                                        </div>
                                        {revision.excerpt && (
                                            <div>
                                                <span className="text-sm font-medium text-muted-foreground">Excerpt:</span>
                                                <p className="text-sm">{revision.excerpt}</p>
                                            </div>
                                        )}
                                        <div>
                                            <span className="text-sm font-medium text-muted-foreground">Content Preview:</span>
                                            <p className="text-sm text-muted-foreground">{revision.content_preview}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
