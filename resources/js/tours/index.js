import { driver } from 'driver.js';

/**
 * Tour configurations keyed by Laravel route name.
 * Each value has steps following the driver.js API.
 */
const tours = {
    // ─── STUDENT PAGES ───────────────────────────────
    'dashboard': {
        steps: [
            {
                element: 'nav[aria-label="Progress"]',
                popover: {
                    title: 'Progress Stepper',
                    description: 'This shows your internship journey through 4 stages: Eligibility, Placement, Logbooks, and Completion. Your current stage is highlighted.',
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="student-dashboard-stats"]',
                popover: {
                    title: 'Stats Overview',
                    description: 'Quick stats showing your current stage, weeks completed, document status, logbook progress, and unread notifications.',
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="student-dashboard-actions"]',
                popover: {
                    title: 'Required Actions',
                    description: 'These cards show what you need to do next: upload eligibility documents, register a placement company, and submit weekly logbooks. Locked items will unlock as you progress.',
                    side: 'right',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="student-dashboard-activity"]',
                popover: {
                    title: 'Activity Feed & Dates',
                    description: 'Your recent activity timeline and important semester dates are shown here. Keep an eye on deadlines!',
                    side: 'left',
                    align: 'start',
                },
            },
        ],
    },

    'eligibility.index': {
        steps: [
            {
                popover: {
                    title: 'Eligibility Documents',
                    description: 'This is Stage 1 of your internship journey. You need to upload 3 required documents: Resume, Transcript, and Offer Letter.',
                },
            },
            {
                element: '[data-tour="eligibility-progress"]',
                popover: {
                    title: 'Document Progress',
                    description: 'This bar tracks how many of your documents have been approved. All 3 must be approved to unlock Stage 2 (Placement).',
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="eligibility-documents"]',
                popover: {
                    title: 'Document Upload Cards',
                    description: 'Each card shows a required document. Check the status badge in the top-right corner. Upload PDF files (max 5MB) using the file picker, then click the upload button.',
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="eligibility-guidelines"]',
                popover: {
                    title: 'Document Guidelines',
                    description: 'Review these guidelines before uploading. All documents must be in PDF format.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    'placement.index': {
        steps: [
            {
                popover: {
                    title: 'Placement Registration',
                    description: 'Here you submit your internship company proposals for admin review.',
                },
            },
            {
                element: '[data-tour="placement-status"]',
                popover: {
                    title: 'Current Status',
                    description: 'This shows your placement status. Once your proposals are approved, you can confirm your final company choice.',
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="placement-proposals"]',
                popover: {
                    title: 'Company Proposals',
                    description: 'Fill in details for your company choices: company name, website, address, and job scope. Submit for admin approval.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    'logbooks.index': {
        steps: [
            {
                popover: {
                    title: 'Weekly Logbooks',
                    description: 'Submit weekly logbook entries here. You need to complete 24 weeks of entries during your internship.',
                },
            },
            {
                element: '[data-tour="logbook-new-entry"]',
                popover: {
                    title: 'New Logbook Entry',
                    description: 'Enter your week number, write your activities summary, and attach the signed logsheet PDF. Then click "Submit logbook".',
                    side: 'right',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="logbook-recent-list"]',
                popover: {
                    title: 'Recent Logbooks',
                    description: 'Your submitted logbooks appear here with status badges. Track approval status and supervisor feedback.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    // ─── FACULTY PAGES ───────────────────────────────
    'faculty.dashboard': {
        steps: [
            {
                popover: {
                    title: 'Faculty Dashboard',
                    description: 'This dashboard gives you an overview of your assigned students and their internship progress.',
                },
            },
            {
                element: '[data-tour="faculty-dashboard-stats"]',
                popover: {
                    title: 'Summary Stats',
                    description: 'Quick stats showing logbooks awaiting verification, total assigned students, and students needing revisions.',
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="faculty-dashboard-students"]',
                popover: {
                    title: 'Student Cards',
                    description: 'Each card shows a student\'s progress: verified, pending, and revision counts. Click "Review" to go to their logbooks.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    'faculty.logbooks.index': {
        steps: [
            {
                popover: {
                    title: 'Logbook Verification',
                    description: 'Review and verify student logbook entries. Use filters to find specific entries quickly.',
                },
            },
            {
                element: '[data-tour="faculty-logbook-filters"]',
                popover: {
                    title: 'Filters',
                    description: 'Search by student name, filter by status (pending/verified/revision requested), and filter by due status.',
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="faculty-logbook-queue"]',
                popover: {
                    title: 'Logbook Queue',
                    description: 'Select entries using checkboxes, then use bulk actions to approve or request revisions. Click "Review" to see full details.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    // ─── ADMIN PAGES ─────────────────────────────────
    'admin.dashboard': {
        steps: [
            {
                popover: {
                    title: 'Admin Dashboard',
                    description: 'Overview of the entire internship management system. Monitor users, eligibility applications, and active internships.',
                },
            },
            {
                element: '[data-tour="admin-dashboard-stats"]',
                popover: {
                    title: 'System Stats',
                    description: 'Total users (breakdown by role), pending eligibility reviews, active internships, and pending logbook counts.',
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="admin-dashboard-actions"]',
                popover: {
                    title: 'Quick Actions',
                    description: 'Jump directly to Review Eligibility, Manage Users, or Faculty Assignments from here.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    'admin.eligibility.index': {
        steps: [
            {
                popover: {
                    title: 'Eligibility Review',
                    description: 'Review and approve student eligibility documents. This is Stage 1 of the internship process.',
                },
            },
            {
                element: '[data-tour="admin-eligibility-stats"]',
                popover: {
                    title: 'Stats Overview',
                    description: 'Total applications, pending reviews, approved, and rejected counts at a glance.',
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="admin-eligibility-filters"]',
                popover: {
                    title: 'Status Filters',
                    description: 'Filter by All, Pending, Approved, or Rejected. Use the search box to find specific students.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="admin-eligibility-students"]',
                popover: {
                    title: 'Student Cards',
                    description: 'Each card shows a student\'s 3 required documents with status badges. Click the eye icon to preview PDFs. Use Approve/Reject buttons to process applications.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    'admin.companies.index': {
        steps: [
            {
                popover: {
                    title: 'Company Proposals',
                    description: 'Review student internship company proposals. Each student submits company choices for approval.',
                },
            },
            {
                element: '[data-tour="admin-companies-stats"]',
                popover: {
                    title: 'Proposal Stats',
                    description: 'Total proposals, pending reviews, approved, and rejected counts.',
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="admin-companies-proposals"]',
                popover: {
                    title: 'Proposal Cards',
                    description: 'Cards are grouped by student. Each shows company name, website, address, and job scope. Approve or reject individual proposals.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    'admin.users.index': {
        steps: [
            {
                popover: {
                    title: 'User Management',
                    description: 'View and manage all users in the system. Create faculty and admin accounts here.',
                },
            },
            {
                element: '[data-tour="admin-users-stats"]',
                popover: {
                    title: 'User Stats',
                    description: 'Breakdown of total users by role: Students, Faculty, and Admins.',
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="admin-users-filters"]',
                popover: {
                    title: 'Role Filters',
                    description: 'Filter users by role. Use the search box to find users by name, email, student ID, or program code.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="admin-users-table"]',
                popover: {
                    title: 'Users Table',
                    description: 'Click the edit icon to change a user\'s role. Click "Add User" in the header to create new faculty or admin accounts.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    'admin.assignments.index': {
        steps: [
            {
                popover: {
                    title: 'Faculty Assignments',
                    description: 'Assign faculty supervisors to students with active internships.',
                },
            },
            {
                element: '[data-tour="admin-assignments-stats"]',
                popover: {
                    title: 'Assignment Stats',
                    description: 'Total internships, assigned count, and unassigned count.',
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="admin-assignments-table"]',
                popover: {
                    title: 'Assignments Table',
                    description: 'Click "Assign" or "Change" to set a faculty supervisor for each student. Use "Auto assign" to distribute unassigned students evenly among faculty.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },
};

/**
 * Start a tour for the given route name.
 */
export function startTour(routeName) {
    const config = tours[routeName];
    if (!config) return false;

    // Small delay to ensure DOM is fully rendered after wire:navigate
    setTimeout(() => {
        const driverInstance = driver({
            showProgress: true,
            animate: true,
            overlayColor: 'rgba(0, 0, 0, 0.6)',
            stagePadding: 8,
            stageRadius: 12,
            popoverClass: 'internims-tour-popover',
            nextBtnText: 'Next',
            prevBtnText: 'Back',
            doneBtnText: 'Done',
            progressText: '{{current}} of {{total}}',
            steps: config.steps,
        });
        driverInstance.drive();
    }, 100);

    return true;
}

/**
 * Check if a tour exists for the given route name.
 */
export function hasTour(routeName) {
    return routeName in tours;
}

export default tours;
