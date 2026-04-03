import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

type Form = {
    code: string;
};

export default function TwoFactorChallenge() {
    const { data, setData, post, processing, errors } = useForm<Required<Form>>({
        code: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('two-factor.verify'), {
            preserveScroll: true,
        });
    };

    return (
        <AuthLayout
            title="Two-factor authentication"
            description="Enter the 6-digit code from your authenticator app or a recovery code."
        >
            <Head title="Two-factor challenge" />

            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-2">
                    <Label htmlFor="code">Authentication code</Label>
                    <Input
                        id="code"
                        name="code"
                        type="text"
                        inputMode="numeric"
                        autoComplete="one-time-code"
                        autoFocus
                        required
                        placeholder="000000"
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value.replace(/\s/g, ''))}
                    />
                    <InputError message={errors.code} />
                </div>

                <Button type="submit" className="w-full" disabled={processing}>
                    {processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
                    Continue
                </Button>
            </form>
        </AuthLayout>
    );
}
