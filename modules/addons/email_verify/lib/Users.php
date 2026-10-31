<?php

namespace EmailVerify;

use WHMCS\Database\Capsule;

final class Users
{
    const COLUMNS = ['id', 'first_name', 'last_name', 'email', 'email_verified_at'];

    public static function find($id)
    {
        return Capsule::table('tblusers')->where('id', (int) $id)->first(self::COLUMNS);
    }

    public static function findByEmail($email)
    {
        $email = trim((string) $email);
        if ($email === '') {
            return null;
        }
        return Capsule::table('tblusers')->where('email', $email)->first(self::COLUMNS);
    }

    public static function isVerified($user)
    {
        if (!$user) {
            return false;
        }
        $value = (string) $user->email_verified_at;
        return $value !== '' && strpos($value, '0000-00-00') !== 0;
    }

    public static function displayName($user)
    {
        return trim($user->first_name . ' ' . $user->last_name);
    }

    /**
     * @return int Number of rows changed.
     */
    public static function setVerified(array $ids, $verified)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return 0;
        }
        $query = Capsule::table('tblusers')->whereIn('id', $ids);
        if ($verified) {
            return $query->whereNull('email_verified_at')->update(['email_verified_at' => date('Y-m-d H:i:s')]);
        }
        return $query->whereNotNull('email_verified_at')->update(['email_verified_at' => null]);
    }

    public static function verifyAll()
    {
        return Capsule::table('tblusers')->whereNull('email_verified_at')->update(['email_verified_at' => date('Y-m-d H:i:s')]);
    }

    public static function search($term, $status)
    {
        $query = Capsule::table('tblusers');
        if ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $query->where(function ($q) use ($term, $like) {
                $q->where('email', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere(Capsule::raw("CONCAT(first_name, ' ', last_name)"), 'like', $like);
                if (ctype_digit($term)) {
                    $q->orWhere('id', (int) $term);
                }
            });
        }
        if ($status === 'verified') {
            $query->whereNotNull('email_verified_at');
        } elseif ($status === 'unverified') {
            $query->whereNull('email_verified_at');
        }
        return $query;
    }
}
