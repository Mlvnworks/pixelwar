<?php
$announcementTypes = [
    'announcement_all' => ['label' => 'Students & Teachers', 'tone' => 'cyan'],
    'announcement_student' => ['label' => 'Students Only', 'tone' => 'yellow'],
    'announcement_teacher' => ['label' => 'Teachers Only', 'tone' => 'mint'],
];
$adminAnnouncementRows = [];

if (isset($connection) && $connection instanceof mysqli) {
    $announcementStatement = $connection->prepare(
        'SELECT
            notifications.notif_id,
            notifications.user_id,
            notifications.text,
            notifications.type,
            notifications.created_at,
            users.username,
            user_details.firstname,
            user_details.lastname
         FROM notifications
         INNER JOIN users ON users.user_id = notifications.user_id
         LEFT JOIN user_details ON user_details.user_id = users.user_id
         WHERE notifications.type IN (?, ?, ?)
         ORDER BY notifications.created_at DESC, notifications.notif_id DESC
         LIMIT 100'
    );
    $typeAll = 'announcement_all';
    $typeStudent = 'announcement_student';
    $typeTeacher = 'announcement_teacher';
    $announcementStatement->bind_param('sss', $typeAll, $typeStudent, $typeTeacher);
    $announcementStatement->execute();
    $adminAnnouncementRows = $announcementStatement->get_result()->fetch_all(MYSQLI_ASSOC);
    $announcementStatement->close();
}
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Broadcast</p>
                <h1 class="mt-1 text-3xl font-bold md:text-4xl">Announcements</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium leading-7 text-arcade-ink/62 md:text-base">
                    Create targeted announcements for students, teachers, or both audiences.
                </p>
            </div>
            <button type="button" class="teacher-button teacher-button--primary gap-2" data-bs-toggle="modal" data-bs-target="#announcement-create-modal">
                <i data-lucide="megaphone" class="h-4 w-4" aria-hidden="true"></i>
                <span>New Announcement</span>
            </button>
        </section>

        <section class="announcement-stat-grid grid gap-4 md:grid-cols-3">
            <?php foreach ($announcementTypes as $typeKey => $typeMeta) : ?>
                <?php
                $typeCount = count(array_filter($adminAnnouncementRows, static fn (array $row): bool => (string) ($row['type'] ?? '') === $typeKey));
                ?>
                <article class="announcement-stat announcement-stat--<?= htmlspecialchars($typeMeta['tone'], ENT_QUOTES, 'UTF-8') ?> rounded-[24px] border-4 border-arcade-ink bg-white p-4 shadow-[6px_6px_0_#26190f]">
                    <p class="text-xs font-black uppercase tracking-[0.14em] text-arcade-ink/50"><?= htmlspecialchars($typeMeta['label'], ENT_QUOTES, 'UTF-8') ?></p>
                    <strong class="mt-2 block text-3xl font-black"><?= (int) $typeCount ?></strong>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="teacher-panel overflow-hidden p-0">
            <div class="flex flex-col gap-3 border-b-4 border-arcade-ink bg-gradient-to-br from-arcade-cyan/35 via-arcade-peach to-arcade-yellow/70 p-5 md:flex-row md:items-end md:justify-between md:p-6">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">History</p>
                    <h2 class="mt-2 text-2xl font-black">Announcement Records</h2>
                </div>
                <span class="teacher-pill bg-white"><?= count($adminAnnouncementRows) ?> total</span>
            </div>

            <div class="announcement-record-grid grid gap-3 p-5 md:p-6">
                <?php if ($adminAnnouncementRows === []) : ?>
                    <article class="rounded-2xl border-2 border-dashed border-arcade-ink/15 bg-white/75 p-6 text-center">
                        <h3 class="text-xl font-black">No announcements yet</h3>
                        <p class="mt-2 text-sm font-bold leading-6 text-arcade-ink/58">Create the first announcement to notify users.</p>
                    </article>
                <?php else : ?>
                    <?php foreach ($adminAnnouncementRows as $announcementRow) : ?>
                        <?php
                        $announcementId = (int) ($announcementRow['notif_id'] ?? 0);
                        $announcementType = (string) ($announcementRow['type'] ?? 'announcement_all');
                        $typeMeta = $announcementTypes[$announcementType] ?? $announcementTypes['announcement_all'];
                        $firstname = trim((string) ($announcementRow['firstname'] ?? ''));
                        $lastname = trim((string) ($announcementRow['lastname'] ?? ''));
                        $author = trim($firstname . ' ' . $lastname);
                        $author = $author !== '' ? $author : (string) ($announcementRow['username'] ?? 'Admin');
                        $createdAt = strtotime((string) ($announcementRow['created_at'] ?? ''));
                        $createdLabel = $createdAt > 0 ? date('M j, Y g:i A', $createdAt) : 'Recently';
                        $announcementText = (string) ($announcementRow['text'] ?? '');
                        $announcementPreview = strlen($announcementText) > 120 ? substr($announcementText, 0, 120) . '...' : $announcementText;
                        ?>
                        <article class="announcement-record rounded-[24px] border-2 border-arcade-ink/12 bg-white p-4">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="teacher-pill bg-arcade-<?= htmlspecialchars($typeMeta['tone'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($typeMeta['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="text-xs font-black uppercase tracking-[0.12em] text-arcade-ink/45"><?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <p class="mt-3 whitespace-pre-wrap text-sm font-bold leading-7 text-arcade-ink/70"><?= htmlspecialchars($announcementText, ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="mt-3 text-xs font-black uppercase tracking-[0.14em] text-arcade-orange">Posted by <?= htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <div class="flex shrink-0 flex-wrap gap-2 lg:justify-end">
                                    <button
                                        type="button"
                                        class="teacher-button teacher-button--light gap-2"
                                        data-bs-toggle="modal"
                                        data-bs-target="#announcement-edit-modal"
                                        data-announcement-id="<?= (int) $announcementId ?>"
                                        data-announcement-type="<?= htmlspecialchars($announcementType, ENT_QUOTES, 'UTF-8') ?>"
                                        data-announcement-text="<?= htmlspecialchars($announcementText, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i>
                                        <span>Edit</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="teacher-button teacher-button--light gap-2 text-arcade-coral"
                                        data-bs-toggle="modal"
                                        data-bs-target="#announcement-delete-modal"
                                        data-announcement-id="<?= (int) $announcementId ?>"
                                        data-announcement-preview="<?= htmlspecialchars($announcementPreview, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </section>
</main>

<div class="modal fade" id="announcement-create-modal" tabindex="-1" aria-labelledby="announcement-create-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[26px] border-4 border-arcade-ink bg-arcade-panel shadow-[8px_8px_0_#26190f]">
            <form method="post" action="./?c=announcement" data-announcement-form>
                <?= adminPanelCsrfField() ?>
                <input type="hidden" name="announcement_action" value="create">
                <div class="modal-header border-b-2 border-arcade-ink/10 px-4 py-3">
                    <div>
                        <p class="font-arcade text-[9px] uppercase tracking-[0.18em] text-arcade-cyan">Create</p>
                        <h2 id="announcement-create-modal-title" class="mb-0 mt-1 text-xl font-black">New announcement</h2>
                    </div>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body grid gap-4 px-4 py-4">
                    <label class="grid gap-2">
                        <span class="text-xs font-black uppercase tracking-[0.12em] text-arcade-orange">Audience</span>
                        <select name="type" required class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-3 text-sm font-bold outline-none focus:border-arcade-orange">
                            <?php foreach ($announcementTypes as $typeKey => $typeMeta) : ?>
                                <option value="<?= htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($typeMeta['label'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="grid gap-2">
                        <span class="text-xs font-black uppercase tracking-[0.12em] text-arcade-orange">Message</span>
                        <textarea name="text" required maxlength="2000" rows="7" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-3 text-sm font-bold leading-7 outline-none focus:border-arcade-orange" placeholder="Write the announcement."></textarea>
                    </label>
                </div>
                <div class="modal-footer border-t-2 border-arcade-ink/10 px-4 py-3">
                    <button type="button" class="teacher-button teacher-button--light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="teacher-button teacher-button--primary gap-2" data-announcement-submit>
                        <span class="announcement-submit__spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span>
                        <i data-lucide="send" class="announcement-submit__icon h-4 w-4" aria-hidden="true"></i>
                        <span data-announcement-submit-label>Post</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="announcement-edit-modal" tabindex="-1" aria-labelledby="announcement-edit-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[26px] border-4 border-arcade-ink bg-arcade-panel shadow-[8px_8px_0_#26190f]">
            <form method="post" action="./?c=announcement" data-announcement-form>
                <?= adminPanelCsrfField() ?>
                <input type="hidden" name="announcement_action" value="update">
                <input type="hidden" name="notif_id" id="announcement-edit-id" value="">
                <div class="modal-header border-b-2 border-arcade-ink/10 px-4 py-3">
                    <div>
                        <p class="font-arcade text-[9px] uppercase tracking-[0.18em] text-arcade-cyan">Edit</p>
                        <h2 id="announcement-edit-modal-title" class="mb-0 mt-1 text-xl font-black">Update announcement</h2>
                    </div>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body grid gap-4 px-4 py-4">
                    <label class="grid gap-2">
                        <span class="text-xs font-black uppercase tracking-[0.12em] text-arcade-orange">Audience</span>
                        <select name="type" id="announcement-edit-type" required class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-3 text-sm font-bold outline-none focus:border-arcade-orange">
                            <?php foreach ($announcementTypes as $typeKey => $typeMeta) : ?>
                                <option value="<?= htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($typeMeta['label'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="grid gap-2">
                        <span class="text-xs font-black uppercase tracking-[0.12em] text-arcade-orange">Message</span>
                        <textarea name="text" id="announcement-edit-text" required maxlength="2000" rows="7" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-3 text-sm font-bold leading-7 outline-none focus:border-arcade-orange"></textarea>
                    </label>
                </div>
                <div class="modal-footer border-t-2 border-arcade-ink/10 px-4 py-3">
                    <button type="button" class="teacher-button teacher-button--light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="teacher-button teacher-button--primary gap-2" data-announcement-submit>
                        <span class="announcement-submit__spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span>
                        <i data-lucide="save" class="announcement-submit__icon h-4 w-4" aria-hidden="true"></i>
                        <span data-announcement-submit-label>Save</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="announcement-delete-modal" tabindex="-1" aria-labelledby="announcement-delete-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[26px] border-4 border-arcade-ink bg-arcade-panel shadow-[8px_8px_0_#26190f]">
            <form method="post" action="./?c=announcement" data-announcement-form>
                <?= adminPanelCsrfField() ?>
                <input type="hidden" name="announcement_action" value="delete">
                <input type="hidden" name="notif_id" id="announcement-delete-id" value="">
                <div class="modal-header border-b-2 border-arcade-ink/10 px-4 py-3">
                    <div>
                        <p class="font-arcade text-[9px] uppercase tracking-[0.18em] text-arcade-coral">Delete</p>
                        <h2 id="announcement-delete-modal-title" class="mb-0 mt-1 text-xl font-black">Delete announcement?</h2>
                    </div>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <p class="text-sm font-bold leading-7 text-arcade-ink/65">This announcement will be removed from student and teacher notification lists.</p>
                    <div class="mt-3 rounded-2xl border-2 border-arcade-ink/10 bg-white/80 p-3">
                        <p id="announcement-delete-preview" class="text-sm font-bold leading-6 text-arcade-ink/70"></p>
                    </div>
                </div>
                <div class="modal-footer border-t-2 border-arcade-ink/10 px-4 py-3">
                    <button type="button" class="teacher-button teacher-button--light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="teacher-button teacher-button--primary gap-2 bg-arcade-coral" data-announcement-submit>
                        <span class="announcement-submit__spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span>
                        <i data-lucide="trash-2" class="announcement-submit__icon h-4 w-4" aria-hidden="true"></i>
                        <span data-announcement-submit-label>Delete</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.announcement-stat,
.announcement-record {
    animation: announcement-rise 360ms cubic-bezier(0.2, 0.9, 0.2, 1) both;
}

.announcement-stat--cyan {
    background: linear-gradient(180deg, rgba(76, 201, 240, 0.32), rgba(255, 255, 255, 0.94));
}

.announcement-stat--yellow {
    background: linear-gradient(180deg, rgba(255, 209, 102, 0.36), rgba(255, 255, 255, 0.94));
}

.announcement-stat--mint {
    background: linear-gradient(180deg, rgba(139, 211, 199, 0.36), rgba(255, 255, 255, 0.94));
}

@keyframes announcement-rise {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();

    const editModal = document.getElementById('announcement-edit-modal');
    const editId = document.getElementById('announcement-edit-id');
    const editType = document.getElementById('announcement-edit-type');
    const editText = document.getElementById('announcement-edit-text');
    const deleteModal = document.getElementById('announcement-delete-modal');
    const deleteId = document.getElementById('announcement-delete-id');
    const deletePreview = document.getElementById('announcement-delete-preview');

    editModal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!(trigger instanceof HTMLElement) || !editId || !editType || !editText) {
            return;
        }

        editId.value = trigger.dataset.announcementId || '';
        editType.value = trigger.dataset.announcementType || 'announcement_all';
        editText.value = trigger.dataset.announcementText || '';
    });

    deleteModal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!(trigger instanceof HTMLElement) || !deleteId || !deletePreview) {
            return;
        }

        deleteId.value = trigger.dataset.announcementId || '';
        deletePreview.textContent = trigger.dataset.announcementPreview || 'Selected announcement';
    });

    document.querySelectorAll('[data-announcement-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('[data-announcement-submit]');
            const spinner = button?.querySelector('.announcement-submit__spinner');
            const icon = button?.querySelector('.announcement-submit__icon');
            const label = button?.querySelector('[data-announcement-submit-label]');

            if (!button || !spinner || !icon || !label) {
                return;
            }

            button.disabled = true;
            spinner.classList.remove('hidden');
            icon.classList.add('hidden');
            label.textContent = 'Processing...';
            button.setAttribute('aria-busy', 'true');
        });
    });
});
</script>
