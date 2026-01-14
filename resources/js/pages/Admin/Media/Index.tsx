import { Head, router } from '@inertiajs/react';
import { useState, useCallback } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card } from '@/components/ui/card';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { useDropzone } from 'react-dropzone';
import { Loader2, Image as ImageIcon, File, FileText, Film, Music, Plus } from 'lucide-react';

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
    user: {
        name: string;
    };
}

interface Props {
    media: {
        data: Media[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        type?: string;
    };
}

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: route('admin.dashboard'),
    },
    {
        title: 'Media',
    },
];

export default function Index({ media, filters }: Props) {
    const [uploading, setUploading] = useState(false);
    const [search, setSearch] = useState(filters.search || '');
    const [type, setType] = useState(filters.type || '');

    const onDrop = useCallback(async (acceptedFiles: File[]) => {
        setUploading(true);
        const formData = new FormData();
        acceptedFiles.forEach(file => {
            formData.append('files[]', file);
        });

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
    }, []);

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
    });

    const getFileIcon = (mimeType: string | null) => {
        if (!mimeType) return <File className="w-6 h-6" />;
        if (mimeType.startsWith('image/')) return <ImageIcon className="w-6 h-6" />;
        if (mimeType.startsWith('video/')) return <Film className="w-6 h-6" />;
        if (mimeType.startsWith('audio/')) return <Music className="w-6 h-6" />;
        if (mimeType.startsWith('application/pdf')) return <FileText className="w-6 h-6" />;
        return <File className="w-6 h-6" />;
    };

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get(route('admin.media.index'), { search: value, type }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleTypeFilter = (value: string) => {
        setType(value);
        router.get(route('admin.media.index'), { search, type: value }, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Media" />

            <div className="p-4">
                <div className="flex justify-between items-center mb-4">
                    <div>
                        <h1 className="text-2xl font-bold">Media</h1>
                        <p className="text-muted-foreground">Manage and upload media files</p>
                    </div>
                    <div className="flex gap-2">
                        <Input
                            type="text"
                            placeholder="Search files..."
                            value={search}
                            onChange={(e) => handleSearch(e.target.value)}
                            className="w-64"
                        />
                        <select
                            value={type}
                            onChange={(e) => handleTypeFilter(e.target.value)}
                            className="border rounded px-2"
                        >
                            <option value="">All types</option>
                            <option value="image">Images</option>
                            <option value="video">Videos</option>
                            <option value="audio">Audio</option>
                            <option value="application">Documents</option>
                        </select>
                    </div>
                </div>

                <div
                    {...getRootProps()}
                    className={`border-2 border-dashed rounded-lg p-8 mb-4 text-center cursor-pointer transition-colors ${isDragActive ? 'border-primary bg-primary/5' : 'border-gray-300'
                        }`}
                >
                    <input {...getInputProps()} />
                    {uploading ? (
                        <div className="flex items-center justify-center">
                            <Loader2 className="w-6 h-6 animate-spin mr-2" />
                            <p>Uploading...</p>
                        </div>
                    ) : isDragActive ? (
                        <p>Drop the files here...</p>
                    ) : (
                        <p>Drag and drop files here, or click to select files</p>
                    )}
                </div>

                <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    {media.data.length === 0 ? (
                        <div className="col-span-full">
                            <div className="flex flex-col items-center justify-center py-12 px-4 border rounded-lg">
                                <ImageIcon className="h-12 w-12 text-gray-400 mb-4" />
                                <h3 className="text-lg font-medium mb-1">No media found</h3>
                                <p className="text-center mb-4">
                                    {search || type
                                        ? 'No media found with your filter'
                                        : 'Start by uploading your first media file'}
                                </p>
                                <div {...getRootProps()} className="cursor-pointer">
                                    <Button>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Upload Media
                                    </Button>
                                </div>
                            </div>
                        </div>
                    ) : (
                        media.data.map((item) => (
                            <Card key={item.id} className="overflow-hidden">
                                <div className="aspect-square relative group">
                                    {item.mime_type?.startsWith('image/') ? (
                                        <img
                                            src={item.url}
                                            alt={item.name}
                                            className="w-full h-full object-cover"
                                        />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center bg-gray-100">
                                            {getFileIcon(item.mime_type)}
                                        </div>
                                    )}
                                    <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => router.visit(route('admin.media.show', item.id))}
                                        >
                                            View
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => window.open(route('admin.media.download', item.id))}
                                        >
                                            Download
                                        </Button>
                                    </div>
                                </div>
                                <div className="p-2">
                                    <p className="text-sm font-medium truncate" title={item.name}>
                                        {item.name}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {item.size} • {format(new Date(item.created_at), 'dd MMM yyyy', { locale: id })}
                                    </p>
                                </div>
                            </Card>
                        ))
                    )}
                </div>

                {media.last_page > 1 && (
                    <div className="mt-4 flex justify-center">
                        <div className="flex space-x-2">
                            {Array.from({ length: media.last_page }, (_, i) => i + 1).map(
                                (page) => (
                                    <Button
                                        key={page}
                                        variant={page === media.current_page ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() =>
                                            router.get(route('admin.media.index'), { page, search, type }, { preserveState: true })
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
