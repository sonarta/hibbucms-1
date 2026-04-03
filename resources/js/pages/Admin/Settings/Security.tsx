import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEventHandler, useMemo } from 'react';
import { Copy, LoaderCircle, Shield } from 'lucide-react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { toast } from 'sonner';

type TwoFactorSetup = {
    qrSvg: string;
    secret: string;
};

interface Props {
    twoFactorEnabled: boolean;
    awaitingConfirmation: boolean;
    recoveryCodesRemaining: number;
    twoFactorSetup: TwoFactorSetup | null;
    recoveryCodesFlash: string[] | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Security',
        href: '/admin/settings/security',
    },
];

export default function Security({
    twoFactorEnabled,
    awaitingConfirmation,
    recoveryCodesRemaining,
    twoFactorSetup,
    recoveryCodesFlash,
}: Props) {
    const confirmForm = useForm({ code: '' });
    const disableForm = useForm({ password: '' });
    const recoveryForm = useForm({ password: '' });

    const qrMarkup = useMemo(() => ({ __html: twoFactorSetup?.qrSvg ?? '' }), [twoFactorSetup?.qrSvg]);

    const onConfirm: FormEventHandler = (e) => {
        e.preventDefault();
        confirmForm.post(route('admin.settings.two-factor.confirm'), {
            preserveScroll: true,
            onSuccess: () => confirmForm.reset('code'),
        });
    };

    const onDisable: FormEventHandler = (e) => {
        e.preventDefault();
        disableForm.delete(route('admin.settings.two-factor.destroy'), {
            preserveScroll: true,
            onSuccess: () => disableForm.reset('password'),
        });
    };

    const onRegenerateRecovery: FormEventHandler = (e) => {
        e.preventDefault();
        recoveryForm.post(route('admin.settings.two-factor.recovery'), {
            preserveScroll: true,
            onSuccess: () => recoveryForm.reset('password'),
        });
    };

    const copyCodes = (codes: string[]) => {
        void navigator.clipboard.writeText(codes.join('\n'));
        toast.success('Recovery codes copied to clipboard.');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Security" />

            <SettingsLayout>
                <div className="space-y-8">
                    <HeadingSmall
                        title="Two-factor authentication"
                        description="Add a second step after your password using an authenticator app (TOTP)."
                    />

                    {recoveryCodesFlash && recoveryCodesFlash.length > 0 && (
                        <Alert className="border-amber-500/50 bg-amber-500/5">
                            <Shield className="h-4 w-4" />
                            <AlertTitle>Save your recovery codes</AlertTitle>
                            <AlertDescription className="space-y-3">
                                <p className="text-sm">Each code works once. Store them in a safe place.</p>
                                <ul className="rounded-md bg-muted/50 p-3 font-mono text-sm">
                                    {recoveryCodesFlash.map((c) => (
                                        <li key={c}>{c}</li>
                                    ))}
                                </ul>
                                <Button type="button" variant="outline" size="sm" onClick={() => copyCodes(recoveryCodesFlash)}>
                                    <Copy className="mr-2 h-3.5 w-3.5" />
                                    Copy all
                                </Button>
                            </AlertDescription>
                        </Alert>
                    )}

                    {twoFactorEnabled ? (
                        <div className="space-y-4 rounded-lg border p-4">
                            <p className="text-sm text-muted-foreground">
                                Two-factor authentication is <strong>enabled</strong>. You have{' '}
                                <strong>{recoveryCodesRemaining}</strong> recovery codes remaining.
                            </p>
                            <form onSubmit={onRegenerateRecovery} className="space-y-3 max-w-md">
                                <div className="grid gap-2">
                                    <Label htmlFor="recovery-password">Current password</Label>
                                    <Input
                                        id="recovery-password"
                                        type="password"
                                        autoComplete="current-password"
                                        value={recoveryForm.data.password}
                                        onChange={(e) => recoveryForm.setData('password', e.target.value)}
                                        required
                                    />
                                    <InputError message={recoveryForm.errors.password} />
                                </div>
                                <Button type="submit" variant="secondary" disabled={recoveryForm.processing}>
                                    {recoveryForm.processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
                                    Regenerate recovery codes
                                </Button>
                            </form>

                            <form onSubmit={onDisable} className="space-y-3 max-w-md border-t pt-4">
                                <p className="text-sm font-medium text-destructive">Disable 2FA</p>
                                <div className="grid gap-2">
                                    <Label htmlFor="disable-password">Current password</Label>
                                    <Input
                                        id="disable-password"
                                        type="password"
                                        autoComplete="current-password"
                                        value={disableForm.data.password}
                                        onChange={(e) => disableForm.setData('password', e.target.value)}
                                        required
                                    />
                                    <InputError message={disableForm.errors.password} />
                                </div>
                                <Button type="submit" variant="destructive" disabled={disableForm.processing}>
                                    {disableForm.processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
                                    Disable two-factor authentication
                                </Button>
                            </form>
                        </div>
                    ) : (
                        <div className="space-y-6">
                            {!awaitingConfirmation && !twoFactorSetup && (
                                <div className="flex flex-wrap items-center gap-3">
                                    <Button
                                        type="button"
                                        onClick={() => router.post(route('admin.settings.two-factor.start'))}
                                    >
                                        Enable two-factor authentication
                                    </Button>
                                </div>
                            )}

                            {(awaitingConfirmation || twoFactorSetup) && (
                                <div className="space-y-4 rounded-lg border p-4">
                                    <p className="text-sm text-muted-foreground">
                                        Scan the QR code with your app, then enter the 6-digit code to confirm.
                                    </p>
                                    {twoFactorSetup?.qrSvg && (
                                        <div
                                            className="inline-block rounded-md border bg-white p-2 [&_svg]:block"
                                            dangerouslySetInnerHTML={qrMarkup}
                                        />
                                    )}
                                    {twoFactorSetup?.secret && (
                                        <p className="text-xs text-muted-foreground break-all">
                                            Manual entry: <span className="font-mono">{twoFactorSetup.secret}</span>
                                        </p>
                                    )}
                                    <form onSubmit={onConfirm} className="max-w-xs space-y-3">
                                        <div className="grid gap-2">
                                            <Label htmlFor="confirm-code">Verification code</Label>
                                            <Input
                                                id="confirm-code"
                                                inputMode="numeric"
                                                autoComplete="one-time-code"
                                                value={confirmForm.data.code}
                                                onChange={(e) => confirmForm.setData('code', e.target.value.replace(/\s/g, ''))}
                                                required
                                            />
                                            <InputError message={confirmForm.errors.code} />
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <Button type="submit" disabled={confirmForm.processing}>
                                                {confirmForm.processing && (
                                                    <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                                )}
                                                Confirm and enable
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                onClick={() => router.post(route('admin.settings.two-factor.cancel'))}
                                            >
                                                Cancel
                                            </Button>
                                        </div>
                                    </form>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
