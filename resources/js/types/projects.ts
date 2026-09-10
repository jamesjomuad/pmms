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
    address: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    latitude: number | null;
    longitude: number | null;
    full_address: string | null;
    maps_url: string | null;
    current_stage: ProjectStage;
    team_count?: number;
    created_at?: string;
    updated_at?: string;
};

export type ProjectDetail = Project & {
    team: ProjectTeamMember[];
    stage_history: ProjectStageHistoryEntry[];
    deliverables: Deliverable[];
    activity_log: ActivityLogEntry[];
};

export type Deliverable = {
    id: number;
    name: string;
    file_name: string;
    mime_type: string;
    size: number;
    human_size: string;
    description: string | null;
    stage_id: number;
    stage_key: string;
    uploaded_by: number;
    created_at: string;
    url: string;
    preview_url: string | null;
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

export type ActivityLogEntry = {
    id: number;
    description: string;
    event: string | null;
    causer: {
        id: number;
        name: string;
    } | null;
    properties: Record<string, unknown>;
    created_at: string;
};

export type WorkflowStatus =
    | 'draft'
    | 'pending'
    | 'in_review'
    | 'approved'
    | 'rejected'
    | 'revision'
    | 'cancelled';

export type Submittal = {
    id: number;
    title: string;
    spec_section: string | null;
    revision_number: number;
    status: WorkflowStatus;
    due_date: string | null;
    assigned_to: string | null;
    created_at: string;
};

export type SubmittalDetail = Submittal & {
    description: string | null;
    submitted_at: string | null;
    approved_at: string | null;
    rejection_reason: string | null;
    assignee: {
        id: number;
        name: string;
    } | null;
    comments: Comment[];
    attachments: Attachment[];
    approval_requests: ApprovalRequest[];
};

export type Comment = {
    id: number;
    body: string;
    user: {
        id: number;
        name: string;
    };
    created_at: string;
};

export type Attachment = {
    id: number;
    name: string;
    file_name: string;
    mime_type: string;
    size: number;
    human_size: string;
    created_at: string;
    url: string;
};

export type ApprovalRequest = {
    id: number;
    status: string;
    requested_by: string;
    created_at: string;
    steps: ApprovalStep[];
};

export type ApprovalStep = {
    id: number;
    approver: string;
    approver_id: number | null;
    status: string;
    decided_at: string | null;
    comments: string | null;
    approved_as_noted: boolean;
};
