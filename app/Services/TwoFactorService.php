<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(
        protected Google2FA $google2fa
    ) {}

    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * SVG markup for scanning with an authenticator app.
     */
    public function qrCodeSvg(string $plainSecret, User $user): string
    {
        $url = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $plainSecret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(192, 2),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);

        return $writer->writeString($url);
    }

    public function verifyTotp(string $plainSecret, string $code): bool
    {
        $code = trim($code);

        return $code !== '' && $this->google2fa->verifyKey($plainSecret, $code);
    }

    /**
     * @return list<string>
     */
    public function generatePlainRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)))
            ->all();
    }

    /**
     * @param  list<string>  $plainCodes
     * @return list<string> bcrypt hashes
     */
    public function hashRecoveryCodes(array $plainCodes): array
    {
        return array_map(fn (string $c) => Hash::make($c), $plainCodes);
    }

    /**
     * Try to consume a one-time recovery code. Returns true if a code was used.
     */
    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $code = strtoupper(trim(str_replace(' ', '', $code)));
        if ($code === '' || ! is_array($user->two_factor_recovery_codes)) {
            return false;
        }

        $hashes = $user->two_factor_recovery_codes;
        foreach ($hashes as $i => $hash) {
            if (Hash::check($code, $hash)) {
                unset($hashes[$i]);
                $user->two_factor_recovery_codes = array_values($hashes);
                $user->save();

                return true;
            }
        }

        return false;
    }
}
