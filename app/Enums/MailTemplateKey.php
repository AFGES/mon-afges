<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Keys of the Fin Mail templates the application sends. The rows are created by the
 * seed_fin_mail_templates migration and locked, so admins can edit them but not delete them.
 */
enum MailTemplateKey: string
{
    case DelegateApprovalRequested = 'delegate-approval-requested';
    case VolunteerApprovalRequested = 'volunteer-approval-requested';
    case DelegateApprovalApproved = 'delegate-approval-approved';
    case VolunteerApprovalApproved = 'volunteer-approval-approved';
    case ProxyApprovalRequested = 'proxy-approval-requested';
    case ProxyApproved = 'proxy-approved';
    case MagicLink = 'magic-link';
}
