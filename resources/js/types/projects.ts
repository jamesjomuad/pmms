export type ProjectStatus = 'active' | 'on_hold' | 'closed';

export type ProjectStage = {
    id: number;
    key: string;
    label: string;
};

export type ProjectRole = 'pm' | 'field_tech' | 'estimator' | 'exec' | 'admin';

export type ProjectTeamMember = {
    id: number;
    name: string;
    email: string;
    project_role: ProjectRole;
    project_role_label?: string;
};

export type Project = {
    id: number;
    name: string;
    client_name: string;
    project_number: string;
    awarded_date: string;
    estimated_completion_date: string | null;
    status: ProjectStatus;
    notes: string | null;
    current_stage: ProjectStage;
    team_count?: number;
    created_at?: string;
    updated_at?: string;
};

export type ProjectDetail = Project & {
    team: ProjectTeamMember[];
    stage_history: ProjectStageHistoryEntry[];
};

export type ProjectStageHistoryEntry = {
    id: number;
    from_stage: string | null;
    to_stage: string;
    changed_by: string;
    changed_at: string;
    notes: string | null;
};

export type StageOption = {
    id: number;
    key: string;
    label: string;
};

export type ProjectRoleOption = {
    value: ProjectRole;
    label: string;
};

export type UserOption = {
    id: number;
    name: string;
    email: string;
};

export type TeamAssignment = {
    user_id: number;
    project_role: ProjectRole;
};
