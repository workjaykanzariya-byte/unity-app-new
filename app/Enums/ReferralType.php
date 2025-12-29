<?php

namespace App\Enums;

enum ReferralType: string
{
    case CustomerReferral = 'customer_referral';
    case B2BReferral = 'b2b_referral';
    case B2GReferral = 'b2g_referral';
    case CollaborativeProjects = 'collaborative_projects';
    case ReferralPartnerships = 'referral_partnerships';
    case VendorReferrals = 'vendor_referrals';
    case Others = 'others';

    public static function values(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            self::cases()
        );
    }

    public static function fromInput(?string $input): ?self
    {
        if ($input === null) {
            return null;
        }

        $trimmed = trim($input);

        if ($trimmed === '') {
            return null;
        }

        foreach (self::cases() as $case) {
            if ($case->value === $trimmed) {
                return $case;
            }
        }

        $labels = [
            'Customer Referral' => self::CustomerReferral,
            'B2B Referral' => self::B2BReferral,
            'B2G Referral' => self::B2GReferral,
            'Collaborative Projects' => self::CollaborativeProjects,
            'Referral Partnerships' => self::ReferralPartnerships,
            'Vendor Referrals' => self::VendorReferrals,
            'Others' => self::Others,
        ];

        foreach ($labels as $label => $case) {
            if (strcasecmp($label, $trimmed) === 0) {
                return $case;
            }
        }

        $normalized = strtolower($trimmed);
        $normalized = preg_replace('/[\\s]+/', ' ', $normalized);
        $normalized = preg_replace('/[^a-z0-9 ]+/', ' ', $normalized);
        $normalized = preg_replace('/\\s+/', '_', $normalized);
        $normalized = trim($normalized, '_');

        foreach (self::cases() as $case) {
            if ($case->value === $normalized) {
                return $case;
            }
        }

        return null;
    }

    public function label(): string
    {
        return match ($this) {
            self::CustomerReferral => 'Customer Referral',
            self::B2BReferral => 'B2B Referral',
            self::B2GReferral => 'B2G Referral',
            self::CollaborativeProjects => 'Collaborative Projects',
            self::ReferralPartnerships => 'Referral Partnerships',
            self::VendorReferrals => 'Vendor Referrals',
            self::Others => 'Others',
        };
    }
}
