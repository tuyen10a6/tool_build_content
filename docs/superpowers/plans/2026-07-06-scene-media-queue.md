# Scene Media Queue Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Chuyển luồng tạo audio từ text và convert video sang GIF của scene từ xử lý đồng bộ sang Laravel queue job, giữ nguyên flow submit và hiển thị scene với trạng thái đang xử lý.

**Architecture:** Controller chỉ validate, lưu scene nhanh, lưu tạm MP4 phục vụ job, đặt trạng thái media và dispatch một job `ProcessSceneMediaJob`. Job query lại scene theo ID, cập nhật trạng thái xử lý, gọi `TextToAudioService` và `VideoToGifService`, cập nhật lại audio/gif/duration vào scene và dọn file MP4 tạm.

**Tech Stack:** Laravel, database queue driver, feature tests với `Queue::fake()` và `Http::fake()`, storage fake.

---

### Task 1: Cover async submit behavior with tests

**Files:**
- Modify: `tests/Feature/SceneCreationTest.php`

- [ ] **Step 1: Add failing create/update dispatch tests**
- [ ] **Step 2: Run targeted PHPUnit tests and confirm they fail for missing async behavior**
- [ ] **Step 3: Add failing job-processing test for media completion/failure paths**
- [ ] **Step 4: Run targeted PHPUnit tests and confirm job tests fail before implementation**

### Task 2: Add persistence for queued media processing

**Files:**
- Create: `database/migrations/2026_07_06_000000_add_media_processing_to_scenes_table.php`
- Create: `database/migrations/2026_07_06_000100_create_jobs_table.php`
- Modify: `app/Models/Scene.php`

- [ ] **Step 1: Add scene media status columns and source video temp columns via migration**
- [ ] **Step 2: Add `jobs` table migration for database queue**
- [ ] **Step 3: Extend `Scene` model fillable/casts/helpers for new status fields**

### Task 3: Move synchronous processing to a queue job

**Files:**
- Create: `app/Jobs/ProcessSceneMediaJob.php`
- Modify: `app/Services/VideoToGifService.php`
- Modify: `app/Http/Controllers/SceneController.php`

- [ ] **Step 1: Add a service entry point that can convert a stored local MP4 path into a stored GIF**
- [ ] **Step 2: Implement queue job lifecycle (`pending` → `processing` → `completed` / `failed`)**
- [ ] **Step 3: Refactor scene create/update to save fast, persist temp MP4, dispatch job, and preserve existing scene/image/transition behavior**

### Task 4: Surface processing state in UI

**Files:**
- Modify: `resources/views/contents/show.blade.php`
- Modify: `resources/views/scenes/show.blade.php`

- [ ] **Step 1: Show `Đang xử lý media...` for pending/processing scenes**
- [ ] **Step 2: Show failed status message without breaking existing preview/detail layout**

### Task 5: Verify the refactor end to end

**Files:**
- Modify: `tests/Feature/SceneCreationTest.php`

- [ ] **Step 1: Run focused PHPUnit coverage for scene create/update/job behavior**
- [ ] **Step 2: Review for regression risks around old scenes, cleanup of temp MP4, and failed status visibility**
