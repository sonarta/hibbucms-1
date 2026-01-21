import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { FileText, Plus, Upload, Puzzle, Power, PowerOff, Trash2 } from 'lucide-react';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useState, useRef } from 'react';
import { Badge } from '@/components/ui/badge';

interface Plugin {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    version: string;
    author: string | null;
    author_url: string | null;
    is_active: boolean;
}

interface Props {
    plugins: Plugin[];
}

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: route('admin.dashboard'),
    },
    {
        title: 'Plugins',
    },
];

export default function Index({ plugins }: Props) {
    const [isUploadDialogOpen, setIsUploadDialogOpen] = useState(false);
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [selectedPlugin, setSelectedPlugin] = useState<Plugin | null>(null);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleScan = () => {
        router.post(route('admin.plugins.scan'));
    };

    const handleActivate = (plugin: Plugin) => {
        router.post(route('admin.plugins.activate', plugin.id));
    };

    const handleDeactivate = (plugin: Plugin) => {
        router.post(route('admin.plugins.deactivate', plugin.id));
    };

    const handleDelete = () => {
        if (selectedPlugin) {
            router.delete(route('admin.plugins.destroy', selectedPlugin.id));
            setIsDeleteDialogOpen(false);
            setSelectedPlugin(null);
        }
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            setSelectedFile(e.target.files[0]);
        }
    };

    const handleUpload = () => {
        if (selectedFile) {
            const formData = new FormData();
            formData.append('plugin', selectedFile);
            router.post(route('admin.plugins.upload'), formData);
            setIsUploadDialogOpen(false);
            setSelectedFile(null);
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Plugins" />

            <div className="p-6">
                {/* Header Section */}
                <div className="mb-8">
                    <div className="flex items-center justify-between mb-2">
                        <div className="flex items-center gap-3">
                            <Puzzle className="h-8 w-8 text-primary" />
                            <h1 className="text-3xl font-bold tracking-tight">Plugins</h1>
                        </div>
                        <div className="flex gap-3">
                            <Dialog open={isUploadDialogOpen} onOpenChange={setIsUploadDialogOpen}>
                                <DialogTrigger asChild>
                                    <Button variant="outline" className="shadow-sm">
                                        <Upload className="mr-2 h-4 w-4" />
                                        Upload Plugin
                                    </Button>
                                </DialogTrigger>
                                <DialogContent className="sm:max-w-[425px]">
                                    <DialogHeader>
                                        <DialogTitle className="flex items-center gap-2">
                                            <Upload className="h-5 w-5" />
                                            Upload Plugin
                                        </DialogTitle>
                                        <DialogDescription className="text-sm text-muted-foreground pt-2">
                                            Upload plugin in ZIP format. The ZIP file must contain the plugin.json file and all plugin files.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div className="grid gap-4 py-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="plugin" className="text-sm font-medium">Plugin File (ZIP)</Label>
                                            <Input
                                                id="plugin"
                                                type="file"
                                                accept=".zip"
                                                ref={fileInputRef}
                                                onChange={handleFileChange}
                                                className="cursor-pointer"
                                            />
                                        </div>
                                    </div>
                                    <DialogFooter className="gap-2">
                                        <Button variant="outline" onClick={() => setIsUploadDialogOpen(false)}>
                                            Cancel
                                        </Button>
                                        <Button onClick={handleUpload} disabled={!selectedFile} className="min-w-[100px]">
                                            {selectedFile ? 'Upload' : 'Select File'}
                                        </Button>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                            <Button onClick={handleScan} variant="default" className="shadow-sm">
                                <Plus className="mr-2 h-4 w-4" />
                                Scan Plugins
                            </Button>
                        </div>
                    </div>
                    <p className="text-muted-foreground">Extend your site's functionality with plugins.</p>
                </div>

                {/* Content Section */}
                <div className="rounded-xl border-0 bg-card">
                    {plugins.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-16 px-6">
                            <div className="rounded-full bg-muted p-4 mb-4">
                                <FileText className="h-8 w-8 text-muted-foreground" />
                            </div>
                            <h3 className="text-xl font-semibold mb-2">No plugins found</h3>
                            <p className="text-muted-foreground text-center max-w-md mb-6">
                                Add a new plugin by uploading a ZIP file or placing it directly in the plugins directory
                            </p>
                            <div className="flex gap-3">
                                <Button onClick={handleScan} variant="outline" className="shadow-sm">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Scan Plugins
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 p-6">
                            {plugins.map((plugin) => (
                                <Card key={plugin.id} className="overflow-hidden">
                                    <CardHeader>
                                        <CardTitle className="flex items-center justify-between">
                                            {plugin.name}
                                            {plugin.is_active ? (
                                                <Badge variant="default" className="bg-green-500">Active</Badge>
                                            ) : (
                                                <Badge variant="secondary">Inactive</Badge>
                                            )}
                                        </CardTitle>
                                        <CardDescription>
                                            Version {plugin.version}
                                            {plugin.author && (
                                                <>
                                                    {' by '}
                                                    {plugin.author_url ? (
                                                        <a href={plugin.author_url} target="_blank" rel="noopener noreferrer" className="underline">
                                                            {plugin.author}
                                                        </a>
                                                    ) : (
                                                        plugin.author
                                                    )}
                                                </>
                                            )}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-sm text-muted-foreground">
                                            {plugin.description || 'No description available'}
                                        </p>
                                    </CardContent>
                                    <CardFooter className="flex gap-2">
                                        {plugin.is_active ? (
                                            <Button
                                                variant="outline"
                                                onClick={() => handleDeactivate(plugin)}
                                                className="flex-1"
                                            >
                                                <PowerOff className="mr-2 h-4 w-4" />
                                                Deactivate
                                            </Button>
                                        ) : (
                                            <>
                                                <Button
                                                    variant="default"
                                                    onClick={() => handleActivate(plugin)}
                                                    className="flex-1"
                                                >
                                                    <Power className="mr-2 h-4 w-4" />
                                                    Activate
                                                </Button>
                                                <Dialog open={isDeleteDialogOpen && selectedPlugin?.id === plugin.id} onOpenChange={(open) => {
                                                    setIsDeleteDialogOpen(open);
                                                    if (!open) setSelectedPlugin(null);
                                                }}>
                                                    <DialogTrigger asChild>
                                                        <Button variant="destructive" size="icon" onClick={() => setSelectedPlugin(plugin)}>
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogHeader>
                                                            <DialogTitle>Delete Plugin</DialogTitle>
                                                            <DialogDescription>
                                                                Are you sure you want to delete "{plugin.name}"? This action cannot be undone.
                                                                The plugin files will be permanently removed from the server.
                                                            </DialogDescription>
                                                        </DialogHeader>
                                                        <DialogFooter>
                                                            <Button variant="outline" onClick={() => setIsDeleteDialogOpen(false)}>
                                                                Cancel
                                                            </Button>
                                                            <Button variant="destructive" onClick={handleDelete}>
                                                                Delete Plugin
                                                            </Button>
                                                        </DialogFooter>
                                                    </DialogContent>
                                                </Dialog>
                                            </>
                                        )}
                                    </CardFooter>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
