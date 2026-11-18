<?php

namespace App\Enums;

/**
 * Permissions applicatives. Chaque valeur est le nom stocké en base.
 */
enum Permission: string
{
    case AccessAdmin = 'admin.access';
    case ManageUsers = 'users.manage';
    case ManageRoles = 'roles.manage';
    case ManageOrganizations = 'organizations.manage';
    case ModeratePublications = 'publications.moderate';
    case ManagePublications = 'publications.manage';
    case ManageReports = 'reports.manage';
    case ManageTaxonomy = 'taxonomy.manage';
    case ManageTerritories = 'territories.manage';
    case ManagePartners = 'partners.manage';
    case ManageVisibility = 'visibility.manage';
    case ManageMemberships = 'memberships.manage';
    case ViewPayments = 'payments.view';
    case ManageSettings = 'settings.manage';
    case ViewNotificationLogs = 'notifications.view';

    public function label(): string
    {
        return match ($this) {
            self::AccessAdmin => "Accéder à l'administration",
            self::ManageUsers => 'Gérer les utilisateurs',
            self::ManageRoles => 'Gérer les rôles et permissions',
            self::ManageOrganizations => 'Gérer les organisations',
            self::ModeratePublications => 'Modérer les publications',
            self::ManagePublications => 'Gérer toutes les publications',
            self::ManageReports => 'Traiter les signalements',
            self::ManageTaxonomy => 'Gérer catégories et types',
            self::ManageTerritories => 'Gérer territoires et communes',
            self::ManagePartners => 'Gérer les partenaires premium',
            self::ManageVisibility => 'Gérer les offres de visibilité',
            self::ManageMemberships => 'Gérer les adhésions',
            self::ViewPayments => 'Consulter les paiements',
            self::ManageSettings => 'Gérer les paramètres',
            self::ViewNotificationLogs => "Consulter l'historique des notifications",
        };
    }
}
