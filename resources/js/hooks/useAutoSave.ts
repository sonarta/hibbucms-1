import { useState, useEffect, useRef, useCallback } from 'react';
import axios from 'axios';

export type SaveStatus = 'idle' | 'saving' | 'saved' | 'error';

interface AutoSaveOptions {
    /** Delay in milliseconds before triggering auto-save (default: 3000) */
    debounceMs?: number;
    /** Callback when save is successful */
    onSuccess?: (postId: number, savedAt: string) => void;
    /** Callback when save fails */
    onError?: (error: string) => void;
}

interface AutoSaveData {
    title?: string;
    content?: string;
    excerpt?: string;
    category_id?: string;
    featured_image_id?: string;
    tags?: string[];
}

interface UseAutoSaveReturn {
    /** Current save status */
    status: SaveStatus;
    /** Last saved timestamp */
    lastSavedAt: string | null;
    /** Current post ID (updated after first save) */
    postId: number | null;
    /** Error message if save failed */
    error: string | null;
    /** Trigger save with current data */
    triggerSave: (data: AutoSaveData) => void;
    /** Reset the auto-save state */
    reset: () => void;
}

export function useAutoSave(
    initialPostId: number | null = null,
    options: AutoSaveOptions = {}
): UseAutoSaveReturn {
    const { debounceMs = 3000, onSuccess, onError } = options;

    const [status, setStatus] = useState<SaveStatus>('idle');
    const [lastSavedAt, setLastSavedAt] = useState<string | null>(null);
    const [postId, setPostId] = useState<number | null>(initialPostId);
    const [error, setError] = useState<string | null>(null);

    const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const pendingDataRef = useRef<AutoSaveData | null>(null);
    const isSavingRef = useRef(false);

    const performSave = useCallback(async (data: AutoSaveData) => {
        if (isSavingRef.current) {
            // Queue this data for next save
            pendingDataRef.current = data;
            return;
        }

        isSavingRef.current = true;
        setStatus('saving');
        setError(null);

        try {
            const url = postId
                ? route('admin.posts.autosave.update', { post: postId })
                : route('admin.posts.autosave');

            const response = await axios.post(url, data);

            if (response.data.success) {
                setPostId(response.data.post_id);
                setLastSavedAt(response.data.saved_at);
                setStatus('saved');
                onSuccess?.(response.data.post_id, response.data.saved_at);

                // Reset to idle after 2 seconds
                setTimeout(() => {
                    setStatus((prev) => (prev === 'saved' ? 'idle' : prev));
                }, 2000);
            } else {
                throw new Error(response.data.message || 'Failed to save');
            }
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Failed to save draft';
            setError(errorMessage);
            setStatus('error');
            onError?.(errorMessage);
        } finally {
            isSavingRef.current = false;

            // If there's pending data, save it
            if (pendingDataRef.current) {
                const pendingData = pendingDataRef.current;
                pendingDataRef.current = null;
                performSave(pendingData);
            }
        }
    }, [postId, onSuccess, onError]);

    const triggerSave = useCallback((data: AutoSaveData) => {
        // Clear existing timeout
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        // Set new timeout for debounced save
        timeoutRef.current = setTimeout(() => {
            performSave(data);
        }, debounceMs);
    }, [debounceMs, performSave]);

    const reset = useCallback(() => {
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }
        setStatus('idle');
        setLastSavedAt(null);
        setError(null);
        pendingDataRef.current = null;
    }, []);

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        };
    }, []);

    // Update postId if initialPostId changes
    useEffect(() => {
        if (initialPostId !== null) {
            setPostId(initialPostId);
        }
    }, [initialPostId]);

    return {
        status,
        lastSavedAt,
        postId,
        error,
        triggerSave,
        reset,
    };
}
