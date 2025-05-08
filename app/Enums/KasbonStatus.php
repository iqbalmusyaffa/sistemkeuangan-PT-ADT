<?php
namespace App\Enums;

enum KasbonStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Disbursed = 'disbursed';
    case Settled = 'settled';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Menunggu',
            self::Approved   => 'Disetujui',
            self::Disbursed  => 'Dicairkan',
            self::Settled    => 'Selesai',
            self::Rejected   => 'Ditolak',
        };
    }

    public static function labels(): array
    {
        return [
            self::Pending->value    => self::Pending->label(),
            self::Approved->value   => self::Approved->label(),
            self::Disbursed->value  => self::Disbursed->label(),
            self::Settled->value    => self::Settled->label(),
            self::Rejected->value   => self::Rejected->label(),
        ];
    }
}
