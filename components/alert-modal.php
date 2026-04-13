<!-- Modal -->
<div class="modal fade" id="alert-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-[28px] border-4 border-arcade-ink/10 bg-arcade-panel shadow-arcade">
      <div class="modal-header border-b-0 px-5 pt-5">
        <?php
        $alertIsError = !empty($_SESSION['alert']['error']);
        $alertTitle = $alertIsError ? 'System Notice' : 'Action Complete';
        ?>
        <h1 class="modal-title font-arcade text-[10px] uppercase tracking-[0.24em] text-<?= $alertIsError ? 'arcade-coral' : 'arcade-orange' ?>" id="staticBackdropLabel">
          <?= htmlspecialchars($alertTitle, ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <button type="button" class="btn-close !text-arcade-ink opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-5 pb-5 pt-2">
        <?php $alertContent = htmlspecialchars((string) ($_SESSION['alert']['content'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        <div class="rounded-[24px] border-2 border-arcade-ink/10 bg-<?= $alertIsError ? 'arcade-coral' : 'arcade-yellow' ?>/15 px-4 py-5 text-center">
          <h5 class="mb-0 text-base font-semibold leading-7 text-arcade-ink"><?= $alertContent ?></h5>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Button trigger modal -->
<button type="button" class="d-none" id="alert-btn" data-bs-toggle="modal" data-bs-target="#alert-modal"></button>


<script>
    const trigBtn = document.querySelector("#alert-btn");

    if (trigBtn) {
        trigBtn.click();
    }
</script>
