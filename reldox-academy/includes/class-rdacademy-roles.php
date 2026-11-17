<?php
/**
 * Classe Rdacademy_Roles
 * Gestisce ruoli e permessi utente.
 *
 * Copiato da Reldox_Roles, RelDox CRD 3.55.09G. TAGLIO: can_access_document()
 * (riservatezza dei documenti del CRM).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Rdacademy_Roles {

    /**
     * Verifica se l'utente corrente ha un determinato permesso.
     * I backdoor hanno sempre accesso a tutto.
     *
     * @param string $module   Modulo (chiave libera del prodotto, es. settings, users)
     * @param string $level    Livello: read, write, delete
     */
    public static function can( string $module, string $level = 'read' ): bool {
        $user = Rdacademy_Auth::get_current_user();
        if ( ! $user ) return false;
        if ( $user['is_backdoor'] ) return true;

        $levels = [ 'read' => 1, 'write' => 2, 'delete' => 3 ];
        $required = $levels[ $level ] ?? 1;

        foreach ( $user['roles'] as $role ) {
            $perms = is_string( $role['permissions'] )
                ? json_decode( $role['permissions'], true )
                : $role['permissions'];

            if ( empty( $perms ) ) continue;
            if ( ! empty( $perms['all'] ) ) return true;

            $role_level = $perms[ $module ] ?? null;
            if ( ! $role_level ) continue;

            $role_num = match ( $role_level ) {
                'full'  => 3,
                'write' => 2,
                'read'  => 1,
                default => 0,
            };

            if ( $role_num >= $required ) return true;
        }

        return false;
    }

    /**
     * Restituisce tutti i ruoli disponibili nel sistema.
     */
    public static function get_all_roles(): array {
        $p = RDACADEMY_TABLE_PREFIX;
        return Rdacademy_DB::get_results( "SELECT * FROM `{$p}roles` ORDER BY is_backdoor DESC, name ASC" );
    }

    /**
     * Restituisce i ruoli dell'utente corrente come array di nomi.
     */
    public static function get_current_user_role_names(): array {
        $user = Rdacademy_Auth::get_current_user();
        if ( ! $user ) return [];
        return array_column( $user['roles'], 'name' );
    }
}
