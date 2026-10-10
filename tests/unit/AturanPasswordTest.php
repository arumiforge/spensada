<?php

use App\Services\Akun\GantiPassword;
use App\Services\Akun\Kredensial;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Password rules of docs/12 SEC-03 and SEC-04 with the docs/11 VAL-21 messages.
 *
 * @internal
 */
final class AturanPasswordTest extends CIUnitTestCase
{
    private const NISN = '0012345678';

    /**
     * @return iterable<string, array{string, string|null, string|null}>
     */
    public static function passwords(): iterable
    {
        yield 'too short' => ['kopi', null, 'Password paling sedikit 8 karakter.'];
        yield 'too long' => [str_repeat('kopi ', 13), null, 'Password paling panjang 64 karakter.'];
        yield '8 accented chars fit' => ['éèêëàâäç', null, null];
        yield 'over 72 bytes' => [str_repeat('é', 37), null, 'Password terlalu panjang. Kurangi huruf beraksen atau simbol.'];
        yield 'common' => ['bismillah', null, 'Password ini terlalu umum dan mudah ditebak. Pilih password lain.'];
        yield 'common with suffix' => ['Indonesia123!', null, 'Password ini terlalu umum dan mudah ditebak. Pilih password lain.'];
        yield 'common from SecLists' => ['Password1', null, 'Password ini terlalu umum dan mudah ditebak. Pilih password lain.'];
        yield 'school word' => ['spensada2026', null, 'Password ini terlalu umum dan mudah ditebak. Pilih password lain.'];
        yield 'NISN' => [self::NISN, null, 'Password tidak boleh memuat NISN atau username.'];
        yield 'NISN inside' => ['zebra0012345678', null, 'Password tidak boleh memuat NISN atau username.'];
        yield 'birth date DDMMYYYY' => ['jalan14032011', '2011-03-14', 'Password tidak boleh memuat tanggal lahir.'];
        yield 'birth date DDMMYY' => ['jalan140311', '2011-03-14', 'Password tidak boleh memuat tanggal lahir.'];
        yield 'birth date YYYYMMDD' => ['20110314jalan', '2011-03-14', 'Password tidak boleh memuat tanggal lahir.'];
        yield 'birth date DD-MM-YYYY' => ['lahir 14-03-2011', '2011-03-14', 'Password tidak boleh memuat tanggal lahir.'];
        yield 'birth date skipped without date' => ['jalan14032011', null, null];
        yield 'repeated' => ['qqqqqqqqq', null, 'Password tidak boleh berupa huruf atau angka yang berulang atau berurutan.'];
        yield 'ascending letters' => ['defghijkl', null, 'Password tidak boleh berupa huruf atau angka yang berulang atau berurutan.'];
        yield 'descending digits' => ['987654320', null, null];
        yield 'descending digits run' => ['876543210', null, 'Password tidak boleh berupa huruf atau angka yang berulang atau berurutan.'];
        yield 'sentence with spaces' => ['sepeda biru di teras', null, null];
        yield 'no composition rule' => ['kucingorenhitam', null, null];
    }

    /**
     * @dataProvider passwords
     */
    public function testRule(string $password, ?string $tanggalLahir, ?string $expected): void
    {
        $this->assertSame($expected, (new GantiPassword())->galat($password, self::NISN, $tanggalLahir));
    }

    public function testUsernameIsCaseInsensitive(): void
    {
        $this->assertSame('Password tidak boleh memuat NISN atau username.', (new GantiPassword())->galat('Hai BuDiSantoso!', 'budisantoso'));
    }

    public function testNewPasswordMustDifferFromCurrent(): void
    {
        $hash    = (new Kredensial())->hash('sepeda biru di teras');
        $service = new GantiPassword();

        $this->assertSame('Password baru harus berbeda dengan password saat ini.', $service->galat('sepeda biru di teras', 'budi', null, $hash));
        $this->assertNull($service->galat('sepeda merah di teras', 'budi', null, $hash));
    }

    public function testCommonListIsLowercaseAndUnique(): void
    {
        $lines = file(GantiPassword::DAFTAR_UMUM, FILE_IGNORE_NEW_LINES);

        $this->assertGreaterThan(10000, count($lines));
        $this->assertSame($lines, array_values(array_unique($lines)));
        $this->assertSame([], array_values(array_filter($lines, static fn (string $l): bool => $l === '' || $l !== mb_strtolower($l))));
        $this->assertContains('bismillah', $lines);
        $this->assertContains('smp1dawe', $lines);
    }
}
