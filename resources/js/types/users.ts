export type UserProject = {
    id: number;
    name: string;
    project_number: string;
    status: string;
    project_role: string | null;
    project_role_label: string | null;
};

export type ManagedUser = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    two_factor_enabled: boolean;
    role: string;
    role_label: string;
    created_at: string;
    projects_count: number;
    projects: UserProject[];
    can: {
        update: boolean;
        delete: boolean;
    };
};

export type UserStats = {
    total: number;
    verified: number;
    admins_owners: number;
    two_factor_enabled: number;
};
