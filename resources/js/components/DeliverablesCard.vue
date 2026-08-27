<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    FileText,
    FileSpreadsheet,
    FileImage,
    File,
    FolderArchive,
    Upload,
    Trash2,
    Download,
    ChevronDown,
    ChevronRight,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Deliverable, StageOption } from '@/types';

const props = defineProps<{
    projectId: number;
    deliverables: Deliverable[];
    stages: StageOption[];
    currentStageId: number;
}>();

const uploadOpen = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const expandedStages = ref<Set<number>>(new Set([props.currentStageId]));

const form = useForm({
    file: null as File | null,
    stage_id: props.currentStageId,
    description: '',
});

const fileName = ref('');
const dragOver = ref(false);

const groupedDeliverables = computed(() => {
    const groups = new Map<number, Deliverable[]>();

    for (const stage of props.stages) {
        groups.set(stage.id, []);
    }

    for (const d of props.deliverables) {
        const stageGroup = groups.get(d.stage_id);

        if (stageGroup) {
            stageGroup.push(d);
        }
    }

    return groups;
});

const totalSize = computed(() => {
    const bytes = props.deliverables.reduce((sum, d) => sum + d.size, 0);

    if (bytes === 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));

    return `${(bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
});

const toggleStage = (stageId: number) => {
    if (expandedStages.value.has(stageId)) {
        expandedStages.value.delete(stageId);
    } else {
        expandedStages.value.add(stageId);
    }
};

const onFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;

    if (input.files?.length) {
        form.file = input.files[0];
        fileName.value = input.files[0].name;
    }
};

const onDrop = (event: DragEvent) => {
    dragOver.value = false;
    const files = event.dataTransfer?.files;

    if (files?.length) {
        form.file = files[0];
        fileName.value = files[0].name;
    }
};

const submitUpload = () => {
    form.post(`/projects/${props.projectId}/deliverables`, {
        onSuccess: () => {
            uploadOpen.value = false;
            form.reset();
            fileName.value = '';
        },
    });
};

const deleteDeliverable = (deliverableId: number) => {
    router.delete(
        `/projects/${props.projectId}/deliverables/${deliverableId}`,
        {
            preserveScroll: true,
        },
    );
};

const fileIcon = (mimeType: string) => {
    if (mimeType.startsWith('image/')) {
        return FileImage;
    }

    if (mimeType.includes('pdf')) {
        return FileText;
    }

    if (mimeType.includes('spreadsheet') || mimeType.includes('excel')) {
        return FileSpreadsheet;
    }

    if (mimeType.includes('zip') || mimeType.includes('archive')) {
        return FolderArchive;
    }

    if (mimeType.includes('word') || mimeType.includes('document')) {
        return FileText;
    }

    return File;
};

const formatBytes = (bytes: number) => {
    if (bytes === 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));

    return `${(bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
};
</script>

<template>
    <div class="rounded-lg border bg-card">
        <div class="flex items-center justify-between border-b px-6 py-4">
            <div>
                <h3 class="text-sm font-semibold">Deliverables</h3>
                <p class="text-xs text-muted-foreground">
                    {{ deliverables.length }} file{{
                        deliverables.length !== 1 ? 's' : ''
                    }}
                    · {{ totalSize }}
                </p>
            </div>
            <Dialog v-model:open="uploadOpen">
                <DialogTrigger as-child>
                    <Button size="sm" class="gap-1.5">
                        <Upload class="h-3.5 w-3.5" />
                        Upload
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Upload Deliverable</DialogTitle>
                        <DialogDescription>
                            Attach a file to this project. Select which stage it
                            belongs to.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitUpload" class="grid gap-4">
                        <div
                            class="flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-6 transition-colors"
                            :class="
                                dragOver
                                    ? 'border-primary bg-primary/5'
                                    : 'border-border'
                            "
                            @dragover.prevent="dragOver = true"
                            @dragleave="dragOver = false"
                            @drop.prevent="onDrop"
                        >
                            <Upload class="h-8 w-8 text-muted-foreground" />
                            <div class="text-center">
                                <p class="text-sm font-medium">
                                    {{
                                        fileName ||
                                        'Drop file here or click to browse'
                                    }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    PDF, images, Office docs, CSV, ZIP — max 20
                                    MB
                                </p>
                            </div>
                            <Input
                                type="file"
                                class="hidden"
                                :ref="
                                    (el: any) => {
                                        fileInput = el?.$el ?? el;
                                    }
                                "
                                @change="onFileChange"
                                accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.zip"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="fileInput?.click()"
                            >
                                Choose File
                            </Button>
                        </div>

                        <div class="grid gap-2">
                            <Label for="stage">Stage</Label>
                            <Select v-model="form.stage_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select stage" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="stage in stages"
                                        :key="stage.id"
                                        :value="stage.id"
                                    >
                                        {{ stage.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <Input
                                id="description"
                                v-model="form.description"
                                placeholder="Optional description"
                            />
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                @click="uploadOpen = false"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                :disabled="!form.file || form.processing"
                            >
                                {{
                                    form.processing ? 'Uploading...' : 'Upload'
                                }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <div v-if="deliverables.length === 0" class="px-6 py-12 text-center">
            <FileText class="mx-auto h-10 w-10 text-muted-foreground/40" />
            <p class="mt-2 text-sm font-medium text-muted-foreground">
                No deliverables yet
            </p>
            <p class="text-xs text-muted-foreground">
                Upload submittals, shop drawings, reports, and other project
                documents.
            </p>
        </div>

        <div v-else class="divide-y">
            <div v-for="stage in stages" :key="stage.id">
                <button
                    v-if="(groupedDeliverables.get(stage.id) ?? []).length > 0"
                    type="button"
                    class="flex w-full items-center gap-2 px-6 py-2.5 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase hover:bg-muted/30"
                    @click="toggleStage(stage.id)"
                >
                    <ChevronRight
                        v-if="!expandedStages.has(stage.id)"
                        class="h-3.5 w-3.5"
                    />
                    <ChevronDown v-else class="h-3.5 w-3.5" />
                    {{ stage.label }}
                    <Badge variant="secondary" class="ml-auto text-[10px]">
                        {{ (groupedDeliverables.get(stage.id) ?? []).length }}
                    </Badge>
                </button>

                <ul v-if="expandedStages.has(stage.id)" class="divide-y">
                    <li
                        v-for="deliverable in groupedDeliverables.get(stage.id)"
                        :key="deliverable.id"
                        class="flex items-center gap-3 px-6 py-3 transition-colors hover:bg-muted/20"
                    >
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-muted"
                        >
                            <component
                                :is="fileIcon(deliverable.mime_type)"
                                class="h-4 w-4 text-muted-foreground"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ deliverable.file_name }}
                            </p>
                            <div
                                class="flex items-center gap-2 text-xs text-muted-foreground"
                            >
                                <span>{{ formatBytes(deliverable.size) }}</span>
                                <span>·</span>
                                <span>{{
                                    new Date(
                                        deliverable.created_at,
                                    ).toLocaleDateString()
                                }}</span>
                                <template v-if="deliverable.description">
                                    <span>·</span>
                                    <span class="truncate">{{
                                        deliverable.description
                                    }}</span>
                                </template>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8"
                                as-child
                            >
                                <a
                                    :href="deliverable.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <Download class="h-4 w-4" />
                                </a>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 text-destructive hover:text-destructive"
                                @click="deleteDeliverable(deliverable.id)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
