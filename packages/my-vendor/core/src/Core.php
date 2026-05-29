<?php

namespace VHAP\Core;

class Core
{
    /**
     * The landlord user model that should be used by the package.
     *
     * @var string
     */
    public static string $landlordUserModel = \VHAP\Core\Models\LandlordUser::class;

    /**
     * Instruct the package to use a custom LandlordUser model.
     *
     * @param string $model
     * @return void
     */
    public static function useLandlordUserModel(string $model): void
    {
        static::$landlordUserModel = $model;

        // Dynamically overwrite the Laravel auth provider model in memory
        config(['auth.providers.landlord_users.model' => $model]);
    }
}
