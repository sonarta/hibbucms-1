import { Head, router, Link } from '@inertiajs/react';
import { useState, useCallback } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { useDropzone } from 'react-dropzone';
import { Loader2, Image as ImageIcon, File, FileText, Film, Music, Plus, Folder, FolderPlus, ChevronRight } from 'lucide-react';

interface Media {
    id: number;
    name: string;
    url: string;
    mime_type: string;
    size: string;
    dimensions?: {
        width: number;
        height: number;
    };
    created_at: string;
    is_folder: boolean;
    items_count?: number;
    user: {
        name: string;
    } | null;
}

interface Folder {
    id: number;
    name: string;
    is_folder: true;
    items_count: number;
}

interface Breadcrumb {
    id: number;
    name: string;
}

interface Props {
    folders: Folder[];
    allFolders: { id: number; name: string; parent_id: number | null }[];
    media: {
        data: Media[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    currentFolder: Folder | null;
    breadcrumbs: Breadcrumb[];
    filters: {
        search?: string;
        type?: string;
        folder_id?: string;
    };
}

export default function Index({ media, folders, allFolders, currentFolder, breadcrumbs, filters }: Props) {
    const [uploading, setUploading] = useState(false);
    const [search, setSearch] = useState(filters.search || '');
    const [type, setType] = useState(filters.type || '');
    const [isCreateFolderOpen, setIsCreateFolderOpen] = useState(false);
    const [newFolderName, setNewFolderName] = useState('');

    // Multi-select state
    const [isSelectMode, setIsSelectMode] = useState(false);
    const [selectedMedia, setSelectedMedia] = useState<number[]>([]);
    const [selectedFolders, setSelectedFolders] = useState<number[]>([]);
    const [isMoveDialogOpen, setIsMoveDialogOpen] = useState(false);
    const [targetFolderId, setTargetFolderId] = useState<string>(''); // string because select value

    const onDrop = useCallback(async (acceptedFiles: File[]) => {
        setUploading(true);
        const formData = new FormData();
        acceptedFiles.forEach(file => {
            formData.append('files[]', file);
        });

        if (currentFolder) {
            formData.append('folder_id', currentFolder.id.toString());
        }

        try {
            router.post(route('admin.media.store'), formData, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    setUploading(false);
                },
                onError: (errors) => {
                    setUploading(false);
                    console.error('Upload failed:', errors);
                },
                onFinish: () => {
                    setUploading(false);
                }
            });
        } catch (error) {
            console.error('Upload failed:', error);
            setUploading(false);
        }
    }, [currentFolder]);

    const { getRootProps, getInputProps, isDragActive } = useDropzone({
        onDrop,
        accept: {
            'image/*': [],
            'video/*': [],
            'audio/*': [],
            'application/pdf': [],
            'application/msword': [],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': [],
        },
        noClick: true,
        disabled: isSelectMode // Disable dropzone in select mode to avoid confusion
    });

    const getFileIcon = (mimeType: string | null) => {
        if (!mimeType) return <File className="w-10 h-10 text-gray-400" />;
        if (mimeType.startsWith('image/')) return <ImageIcon className="w-10 h-10 text-blue-500" />;
        if (mimeType.startsWith('video/')) return <Film className="w-10 h-10 text-red-500" />;
        if (mimeType.startsWith('audio/')) return <Music className="w-10 h-10 text-yellow-500" />;
        if (mimeType.startsWith('application/pdf')) return <FileText className="w-10 h-10 text-red-600" />;
        return <File className="w-10 h-10 text-gray-400" />;
    };

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get(route('admin.media.index'), { search: value, type, folder_id: currentFolder?.id }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleTypeFilter = (value: string) => {
        setType(value);
        router.get(route('admin.media.index'), { search, type: value, folder_id: currentFolder?.id }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleCreateFolder = () => {
        router.post(route('admin.media.folders.store'), {
            name: newFolderName,
            parent_id: currentFolder?.id
        }, {
            onSuccess: () => {
                setIsCreateFolderOpen(false);
                setNewFolderName('');
            }
        });
    };

    const handleDeleteFolder = (id: number) => {
        if (confirm('Are you sure you want to delete this folder? All contents will be moved to the root/parent folder.')) {
            router.delete(route('admin.media.folders.destroy', id), {
                preserveScroll: true
            });
        }
    };

    // Selection Handlers
    const toggleSelectMode = () => {
        setIsSelectMode(!isSelectMode);
        setSelectedMedia([]);
        setSelectedFolders([]);
    };

    const toggleMediaSelection = (id: number) => {
        if (selectedMedia.includes(id)) {
            setSelectedMedia(selectedMedia.filter(i => i !== id));
        } else {
            setSelectedMedia([...selectedMedia, id]);
        }
    };

    const toggleFolderSelection = (id: number) => {
        if (selectedFolders.includes(id)) {
            setSelectedFolders(selectedFolders.filter(i => i !== id));
        } else {
            setSelectedFolders([...selectedFolders, id]);
        }
    };

    const handleBulkDelete = () => {
        if (confirm(`Are you sure you want to delete ${selectedMedia.length} files and ${selectedFolders.length} folders?`)) {
            router.post(route('admin.media.bulk-destroy'), {
                ids: selectedMedia,
                folder_ids: selectedFolders
            }, {
                onSuccess: () => {
                    setSelectedMedia([]);
                    setSelectedFolders([]);
                    if (media.data.length === 0 && folders.length === 0) setIsSelectMode(false);
                }
            });
        }
    };

    const handleBulkMove = () => {
        const targetId = targetFolderId === 'root' ? null : parseInt(targetFolderId);

        router.post(route('admin.media.move'), {
            ids: selectedMedia,
            folder_ids: selectedFolders,
            target_folder_id: targetId
        }, {
            onSuccess: () => {
                setIsMoveDialogOpen(false);
                setSelectedMedia([]);
                setSelectedFolders([]);
                setIsSelectMode(false);
            }
        });
    };

    // Combine static and dynamic breadcrumbs
    const pageBreadcrumbs = [
        {
            title: 'Dashboard',
            href: route('admin.dashboard'),
        },
        {
            title: 'Media',
            href: route('admin.media.index'),
        },
        ...breadcrumbs.map(b => ({
            title: b.name,
            href: route('admin.media.index', { folder_id: b.id })
        }))
    ];

    if (currentFolder) {
        pageBreadcrumbs.push({
            title: currentFolder.name,
            href: "#" // Current page
        });
    }

    return (
        <AppLayout breadcrumbs={pageBreadcrumbs}>
            <Head title="Media" />

            <div className="p-6 h-full flex flex-col" {...getRootProps()}>
                <input {...getInputProps()} />

                {/* Drag Overlay (same as before) */}
                {isDragActive && (
                    <div className="fixed inset-0 z-50 bg-primary/20 backdrop-blur-sm flex items-center justify-center border-4 border-dashed border-primary m-4 rounded-xl">
                        <div className="bg-background p-8 rounded-xl shadow-lg flex flex-col items-center animate-in zoom-in duration-300">
                            <Loader2 className="w-12 h-12 text-primary animate-spin mb-4" />
                            <h3 className="text-2xl font-bold text-primary">Drop files to upload</h3>
                            <p className="text-muted-foreground mt-2">Files will be uploaded to {currentFolder ? currentFolder.name : 'root'}</p>
                        </div>
                    </div>
                )}

                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Media Library</h1>
                        <p className="text-muted-foreground">Manage files and folders</p>
                    </div>

                    {/* Toolbar */}
                    <div className="flex gap-3 w-full md:w-auto items-center">
                        {isSelectMode ? (
                            <div className="flex gap-2 items-center bg-accent/20 p-1 rounded-lg border animate-in fade-in slide-in-from-right-4">
                                <span className="text-sm font-medium px-2">
                                    {selectedMedia.length + selectedFolders.length} selected
                                </span>
                                <div className="h-4 w-px bg-border mx-1" />
                                <Button size="sm" variant="ghost" onClick={() => setIsMoveDialogOpen(true)} disabled={selectedMedia.length === 0 && selectedFolders.length === 0}>
                                    Move
                                </Button>
                                <Button size="sm" variant="ghost" className="text-destructive hover:text-destructive" onClick={handleBulkDelete} disabled={selectedMedia.length === 0 && selectedFolders.length === 0}>
                                    Delete
                                </Button>
                                <div className="h-4 w-px bg-border mx-1" />
                                <Button size="sm" variant="outline" onClick={toggleSelectMode}>
                                    Cancel
                                </Button>
                            </div>
                        ) : (
                            <>
                                <div className="relative flex-1 md:w-64">
                                    <Input
                                        type="text"
                                        placeholder="Search files..."
                                        value={search}
                                        onChange={(e) => handleSearch(e.target.value)}
                                        className="w-full"
                                    />
                                </div>
                                <Button variant={isSelectMode ? "secondary" : "outline"} onClick={toggleSelectMode}>
                                    Select
                                </Button>
                                <Dialog open={isCreateFolderOpen} onOpenChange={setIsCreateFolderOpen}>
                                    <DialogTrigger asChild>
                                        <Button variant="outline">
                                            <FolderPlus className="mr-2 h-4 w-4" />
                                            New Folder
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Create New Folder</DialogTitle>
                                            <DialogDescription>
                                                Create a folder to organize your media files.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="grid gap-4 py-4">
                                            <div className="grid gap-2">
                                                <Label htmlFor="name">Folder Name</Label>
                                                <Input
                                                    id="name"
                                                    value={newFolderName}
                                                    onChange={(e) => setNewFolderName(e.target.value)}
                                                    placeholder="e.g. Images"
                                                    onKeyDown={(e) => e.key === 'Enter' && handleCreateFolder()}
                                                />
                                            </div>
                                        </div>
                                        <DialogFooter>
                                            <Button variant="outline" onClick={() => setIsCreateFolderOpen(false)}>Cancel</Button>
                                            <Button onClick={handleCreateFolder} disabled={!newFolderName}>Create Folder</Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>

                                <div className="cursor-pointer" onClick={() => (document.querySelector('input[type="file"]') as HTMLInputElement)?.click()}>
                                    <Button>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Upload
                                    </Button>
                                </div>
                            </>
                        )}
                    </div>
                </div>

                {/* Move Dialog */}
                <Dialog open={isMoveDialogOpen} onOpenChange={setIsMoveDialogOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Move Items</DialogTitle>
                            <DialogDescription>
                                Select the destination folder for the selected items.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="py-4">
                            <Label htmlFor="targetFolder">Destination Folder</Label>
                            <select
                                id="targetFolder"
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 mt-2"
                                value={targetFolderId}
                                onChange={(e) => setTargetFolderId(e.target.value)}
                            >
                                <option value="" disabled>Select a folder...</option>
                                <option value="root">Root Folder</option>
                                {allFolders.map(f => (
                                    <option key={f.id} value={f.id} disabled={selectedFolders.includes(f.id)}>
                                        {f.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setIsMoveDialogOpen(false)}>Cancel</Button>
                            <Button onClick={handleBulkMove} disabled={!targetFolderId}>Move Items</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {uploading && (
                    <div className="fixed bottom-6 right-6 z-50 bg-background border shadow-lg rounded-lg p-4 flex items-center gap-3 animate-in slide-in-from-bottom">
                        <Loader2 className="w-5 h-5 animate-spin text-primary" />
                        <div>
                            <p className="font-medium">Uploading files...</p>
                            <p className="text-xs text-muted-foreground">Please wait while we process your uploads.</p>
                        </div>
                    </div>
                )}

                {/* Breadcrumbs */}
                <div className="flex items-center text-sm text-muted-foreground mb-6 overflow-x-auto pb-2">
                    <Link
                        href={route('admin.media.index')}
                        className={`flex items-center hover:text-foreground transition-colors ${!currentFolder ? 'font-semibold text-foreground' : ''}`}
                    >
                        <Folder className="w-4 h-4 mr-1.5" />
                        Root
                    </Link>
                    {breadcrumbs.map((crumb) => (
                        <div key={crumb.id} className="flex items-center">
                            <ChevronRight className="w-4 h-4 mx-1" />
                            <Link
                                href={route('admin.media.index', { folder_id: crumb.id })}
                                className="hover:text-foreground transition-colors"
                            >
                                {crumb.name}
                            </Link>
                        </div>
                    ))}
                    {currentFolder && (
                        <div className="flex items-center">
                            <ChevronRight className="w-4 h-4 mx-1" />
                            <span className="font-semibold text-foreground">{currentFolder.name}</span>
                        </div>
                    )}
                </div>

                <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                    {/* Render Folders */}
                    {folders.map((folder) => {
                        const isSelected = selectedFolders.includes(folder.id);
                        return (
                            <Card
                                key={`folder-${folder.id}`}
                                className={`group relative flex flex-col aspect-square hover:bg-accent/50 transition-colors cursor-pointer border-2 ${isSelected ? 'border-primary bg-accent/20' : 'hover:border-primary/50'}`}
                                onClick={() => {
                                    if (isSelectMode) {
                                        toggleFolderSelection(folder.id);
                                    } else {
                                        router.visit(route('admin.media.index', { folder_id: folder.id }));
                                    }
                                }}
                            >
                                {isSelectMode && (
                                    <div className="absolute top-2 left-2 z-10">
                                        <div className={`w-5 h-5 rounded border ${isSelected ? 'bg-primary border-primary' : 'bg-background border-input'} flex items-center justify-center`}>
                                            {isSelected && <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" className="w-3 h-3 text-primary-foreground"><polyline points="20 6 9 17 4 12" /></svg>}
                                        </div>
                                    </div>
                                )}
                                <div className="flex-1 flex flex-col items-center justify-center p-4">
                                    <Folder className={`w-16 h-16 text-yellow-400 fill-yellow-400/20 transition-transform duration-300 ${!isSelectMode && 'group-hover:scale-110'}`} />
                                </div>
                                <div className="p-3 border-t bg-card/50">
                                    <p className="font-medium text-sm truncate text-center">{folder.name}</p>
                                    <p className="text-xs text-muted-foreground text-center">{folder.items_count} items</p>
                                </div>
                            </Card>
                        );
                    })}

                    {/* Render Media */}
                    {media.data.map((item) => {
                        const isSelected = selectedMedia.includes(item.id);
                        return (
                            <Card
                                key={`media-${item.id}`}
                                className={`group relative flex flex-col aspect-square overflow-hidden hover:shadow-md transition-shadow cursor-pointer ${isSelected ? 'ring-2 ring-primary' : ''}`}
                                onClick={() => {
                                    if (isSelectMode) {
                                        toggleMediaSelection(item.id);
                                    }
                                }}
                            >
                                {isSelectMode && (
                                    <div className="absolute top-2 left-2 z-10">
                                        <div className={`w-5 h-5 rounded border ${isSelected ? 'bg-primary border-primary' : 'bg-background border-input'} flex items-center justify-center`}>
                                            {isSelected && <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" className="w-3 h-3 text-primary-foreground"><polyline points="20 6 9 17 4 12" /></svg>}
                                        </div>
                                    </div>
                                )}

                                <div className="flex-1 relative bg-muted/30">
                                    {item.mime_type?.startsWith('image/') ? (
                                        <img
                                            src={item.url}
                                            alt={item.name}
                                            className={`w-full h-full object-cover transition-transform duration-500 ${!isSelectMode && 'group-hover:scale-105'}`}
                                            loading="lazy"
                                        />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center">
                                            {getFileIcon(item.mime_type)}
                                        </div>
                                    )}

                                    {!isSelectMode && (
                                        <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 p-2 backdrop-blur-[1px]">
                                            <Button
                                                variant="secondary"
                                                size="icon"
                                                className="h-8 w-8 rounded-full"
                                                onClick={(e) => { e.stopPropagation(); router.visit(route('admin.media.show', item.id)); }}
                                                title="View Details"
                                            >
                                                <ImageIcon className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="secondary"
                                                size="icon"
                                                className="h-8 w-8 rounded-full"
                                                onClick={(e) => { e.stopPropagation(); window.open(route('admin.media.download', item.id)); }}
                                                title="Download"
                                            >
                                                <Plus className="h-4 w-4 rotate-180" />
                                            </Button>
                                        </div>
                                    )}
                                </div>
                                <div className="p-3 border-t text-center bg-card">
                                    <p className="text-sm font-medium truncate w-full" title={item.name}>
                                        {item.name}
                                    </p>
                                    <p className="text-[10px] text-muted-foreground uppercase tracking-wider mt-0.5">
                                        {item.mime_type?.split('/')[1] || 'FILE'} • {item.size}
                                    </p>
                                </div>
                            </Card>
                        );
                    })}

                    {folders.length === 0 && media.data.length === 0 && (
                        <div className="col-span-full py-12 flex flex-col items-center justify-center text-center border-2 border-dashed rounded-xl bg-accent/10">
                            <div className="bg-background p-4 rounded-full shadow-sm mb-4">
                                <FolderPlus className="h-8 w-8 text-muted-foreground" />
                            </div>
                            <h3 className="text-lg font-semibold">Empty Folder</h3>
                            <p className="text-muted-foreground max-w-xs mt-1 mb-4">
                                {search ? 'No results found.' : 'Drag files here or create a folder to get started.'}
                            </p>
                            {!search && (
                                <div className="flex gap-2">
                                    <Button variant="outline" onClick={() => setIsCreateFolderOpen(true)}>Create Folder</Button>
                                    <div onClick={() => (document.querySelector('input[type="file"]') as HTMLInputElement)?.click()}>
                                        <Button>Upload Files</Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {media.last_page > 1 && (
                    <div className="mt-8 flex justify-center pb-8">
                        <div className="flex space-x-2">
                            {Array.from({ length: media.last_page }, (_, i) => i + 1).map(
                                (page) => (
                                    <Button
                                        key={page}
                                        variant={page === media.current_page ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() =>
                                            router.get(route('admin.media.index'), { page, search, type, folder_id: currentFolder?.id }, { preserveState: true })
                                        }
                                    >
                                        {page}
                                    </Button>
                                ),
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
