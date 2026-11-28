<?php

declare(strict_types=1);

use App\Web;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Group::create()
        ->routes(
            Route::get('/')
                ->action(Web\HomePage\Action::class)
                ->name('home'),
            Route::get('/login')
                ->action(Web\Login\Action::class)
                ->name('login'),
            Route::get('/site/logout')
                ->action(Web\Logout\Action::class)
                ->name('site-logout'),
            Route::get('/site/notifikasi')
                ->action(Web\Notification\ListAction::class)
                ->name('site-notifikasi'),
            Route::get('/site/read_noti')
                ->action(Web\Notification\ReadAction::class)
                ->name('site-read-noti'),
            Route::get('/site/noti-read')
                ->action(Web\Notification\ReadAction::class)
                ->name('site-noti-read'),
            Route::get('/site/searching')
                ->action(Web\Notification\SearchAction::class)
                ->name('site-searching'),
            Route::get('/site/archive')
                ->action(Web\Notification\ArchiveListAction::class)
                ->name('site-archive'),
            Route::get('/site/read-arc')
                ->action(Web\Notification\ArchiveReadAction::class)
                ->name('site-read-arc'),
            Route::post('/site/mark_read')
                ->action(Web\Notification\MarkReadAction::class)
                ->name('site-mark-read'),
            Route::post('/site/send_archive')
                ->action(Web\Notification\ArchiveAction::class)
                ->name('site-send-archive'),
            Route::get('/dashboard')
                ->action(Web\Dashboard\Action::class)
                ->name('dashboard'),
            Route::get('/dashboard/index')
                ->action(Web\Dashboard\Action::class)
                ->name('dashboard-index'),
            Route::get('/menu')
                ->action(Web\Menu\Action::class)
                ->name('menu'),
            Route::get('/pengesahan-data-staf')
                ->action(Web\PengesahanDataStaf\IndexAction::class)
                ->name('pengesahan-data-staf'),
            Route::get('/pengesahan-data-staf/index')
                ->action(Web\PengesahanDataStaf\IndexAction::class)
                ->name('pengesahan-data-staf-index'),
            Route::get('/pengesahan-data-staf/keluarga')
                ->action(Web\PengesahanDataStaf\Keluarga\IndexAction::class)
                ->name('pengesahan-data-staf-keluarga'),
            Route::post('/pengesahan-data-staf/keluarga/verify')
                ->action(Web\PengesahanDataStaf\Keluarga\VerifyAction::class)
                ->name('pengesahan-data-staf-keluarga-verify'),
            Route::get('/pengesahan-data-staf/hubungan-keluarga')
                ->action(Web\PengesahanDataStaf\HubunganKeluarga\IndexAction::class)
                ->name('pengesahan-data-staf-hubungan-keluarga'),
            Route::post('/pengesahan-data-staf/hubungan-keluarga/verify')
                ->action(Web\PengesahanDataStaf\HubunganKeluarga\VerifyAction::class)
                ->name('pengesahan-data-staf-hubungan-keluarga-verify'),
            Route::get('/pengesahan-data-staf/pds-keluarga')
                ->action(Web\PengesahanDataStaf\Keluarga\IndexAction::class)
                ->name('pengesahan-data-staf-pds-keluarga'),
            Route::post('/pengesahan-data-staf/pds-keluarga')
                ->action(Web\PengesahanDataStaf\Keluarga\VerifyAction::class)
                ->name('pengesahan-data-staf-pds-keluarga-post'),
            Route::get('/pengesahan-data-staf/pds-keluarga-hubungan')
                ->action(Web\PengesahanDataStaf\HubunganKeluarga\IndexAction::class)
                ->name('pengesahan-data-staf-pds-keluarga-hubungan'),
            Route::post('/pengesahan-data-staf/pds-keluarga-hubungan')
                ->action(Web\PengesahanDataStaf\HubunganKeluarga\VerifyAction::class)
                ->name('pengesahan-data-staf-pds-keluarga-hubungan-post'),
            Route::get('/pengesahan-data-staf/keahlian')
                ->action(Web\PengesahanDataStaf\Keahlian\IndexAction::class)
                ->name('pengesahan-data-staf-keahlian'),
            Route::get('/pengesahan-data-staf/keahlian/staf')
                ->action(Web\PengesahanDataStaf\Keahlian\StaffAction::class)
                ->name('pengesahan-data-staf-keahlian-staf'),
            Route::post('/pengesahan-data-staf/keahlian/verify')
                ->action(Web\PengesahanDataStaf\Keahlian\VerifyAction::class)
                ->name('pengesahan-data-staf-keahlian-verify'),
            Route::get('/pengesahan-data-staf/pds-keahlian')
                ->action(Web\PengesahanDataStaf\Keahlian\IndexAction::class)
                ->name('pengesahan-data-staf-pds-keahlian'),
            Route::post('/pengesahan-data-staf/pds-keahlian')
                ->action(Web\PengesahanDataStaf\Keahlian\VerifyAction::class)
                ->name('pengesahan-data-staf-pds-keahlian-post'),
            Route::get('/pengesahan-data-staf/pds-keahlian-individu')
                ->action(Web\PengesahanDataStaf\Keahlian\StaffAction::class)
                ->name('pengesahan-data-staf-pds-keahlian-individu'),
            Route::get('/pengesahan-data-staf/akaun-bank')
                ->action(Web\PengesahanDataStaf\AkaunBank\IndexAction::class)
                ->name('pengesahan-data-staf-akaun-bank'),
            Route::get('/pengesahan-data-staf/akaun-bank/verification')
                ->action(Web\PengesahanDataStaf\AkaunBank\VerificationAction::class)
                ->name('pengesahan-data-staf-akaun-bank-verification'),
            Route::methods(['GET', 'POST'], '/pengesahan-data-staf/akaun-bank/verify')
                ->action(Web\PengesahanDataStaf\AkaunBank\VerifyAction::class)
                ->name('pengesahan-data-staf-akaun-bank-verify'),
            Route::get('/pengesahan-data-staf/akaun-bank/verify-detail')
                ->action(Web\PengesahanDataStaf\AkaunBank\VerificationDetailAction::class)
                ->name('pengesahan-data-staf-akaun-bank-verify-detail'),
            Route::get('/pengesahan-data-staf/akaun-bank/approval')
                ->action(Web\PengesahanDataStaf\AkaunBank\ApprovalAction::class)
                ->name('pengesahan-data-staf-akaun-bank-approval'),
            Route::methods(['GET', 'POST'], '/pengesahan-data-staf/akaun-bank/approve')
                ->action(Web\PengesahanDataStaf\AkaunBank\ApproveAction::class)
                ->name('pengesahan-data-staf-akaun-bank-approve'),
            Route::get('/pengesahan-data-staf/akaun-bank/approve-detail')
                ->action(Web\PengesahanDataStaf\AkaunBank\ApprovalDetailAction::class)
                ->name('pengesahan-data-staf-akaun-bank-approve-detail'),
            Route::get('/pengesahan-data-staf/pds-akaun-bank')
                ->action(Web\PengesahanDataStaf\AkaunBank\IndexAction::class)
                ->name('pengesahan-data-staf-pds-akaun-bank'),
            Route::get('/pengesahan-data-staf/acc-bank-verification')
                ->action(Web\PengesahanDataStaf\AkaunBank\VerificationAction::class)
                ->name('pengesahan-data-staf-acc-bank-verification'),
            Route::methods(['GET', 'POST'], '/pengesahan-data-staf/acc-bank-verify')
                ->action(Web\PengesahanDataStaf\AkaunBank\VerifyAction::class)
                ->name('pengesahan-data-staf-acc-bank-verify'),
            Route::get('/pengesahan-data-staf/acc-bank-verify-details')
                ->action(Web\PengesahanDataStaf\AkaunBank\VerificationDetailAction::class)
                ->name('pengesahan-data-staf-acc-bank-verify-details'),
            Route::get('/pengesahan-data-staf/acc-bank-approval')
                ->action(Web\PengesahanDataStaf\AkaunBank\ApprovalAction::class)
                ->name('pengesahan-data-staf-acc-bank-approval'),
            Route::methods(['GET', 'POST'], '/pengesahan-data-staf/acc-bank-approve')
                ->action(Web\PengesahanDataStaf\AkaunBank\ApproveAction::class)
                ->name('pengesahan-data-staf-acc-bank-approve'),
            Route::get('/pengesahan-data-staf/acc-bank-approve-details')
                ->action(Web\PengesahanDataStaf\AkaunBank\ApprovalDetailAction::class)
                ->name('pengesahan-data-staf-acc-bank-approve-details'),
            Route::get('/lantikan/index')
                ->action(Web\Lantikan\IndexAction::class)
                ->name('lantikan-index'),
            Route::methods(['GET', 'POST'], '/lantikan/lantik-staf-baru')
                ->action(Web\Lantikan\StafBaruFormAction::class)
                ->name('lantikan-lantik-staf-baru'),
            Route::get('/lantikan/lantik-staf-baru/result')
                ->action(Web\Lantikan\ResultAction::class)
                ->name('lantikan-lantik-staf-baru-result'),
            Route::get('/lantikan/lantik-staf-baru/email-check')
                ->action(Web\Lantikan\EmailCheckAction::class)
                ->name('lantikan-lantik-staf-baru-email-check'),
            Route::methods(['GET', 'POST'], '/lantikan/lantik-phs')
                ->action(Web\Lantikan\PhsFormAction::class)
                ->name('lantikan-lantik-phs'),
            Route::methods(['GET', 'POST'], '/lantikan/lantik-khas')
                ->action(Web\Lantikan\KhasFormAction::class)
                ->name('lantikan-lantik-khas'),
            Route::methods(['GET', 'POST'], '/lantikan/lantik-spdp')
                ->action(Web\Lantikan\SpdpFormAction::class)
                ->name('lantikan-lantik-spdp'),
            Route::get('/lantikan/lssu-index')
                ->action(Web\Lantikan\SearchAction::class)
                ->name('lantikan-lssu-index'),
            Route::get('/lantikan/lantikan-belum-selesai')
                ->action(Web\Lantikan\IncompleteAction::class)
                ->name('lantikan-lantikan-belum-selesai'),
            Route::get('/lantikan/view-utama')
                ->action(Web\Lantikan\VerificationAction::class)
                ->name('lantikan-view-utama'),
            Route::get('/lantikan/view-lantikan')
                ->action(Web\Lantikan\VerificationAction::class)
                ->name('lantikan-view-lantikan'),
            Route::get('/lantikan/tkpg')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-tkpg'),
            Route::get('/lantikan/lpg')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-lpg'),
            Route::get('/lantikan/tklpg')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-tklpg'),
            Route::get('/lantikan/lantikan-lpu')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-lantikan-lpu'),
            Route::get('/lantikan/lantik-pli')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-lantik-pli'),
            Route::get('/lantikan/lantik-sambilanakademik')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-lantik-sambilanakademik'),
            Route::get('/lantikan/penerbitums')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-penerbitums'),
            Route::get('/lantikan/lantikan-ejobs')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-lantikan-ejobs'),
            Route::get('/kehadiran/index')
                ->action(Web\Kehadiran\IndexAction::class)
                ->name('kehadiran-index'),
            Route::get('/kehadiran/laporan-kehadiran')
                ->action(Web\Kehadiran\StaffAction::class)
                ->name('kehadiran-laporan-kehadiran'),
            Route::get('/kehadiran/tindakan_ketidakpatuhan')
                ->action(Web\Kehadiran\StaffAction::class)
                ->name('kehadiran-tindakan-ketidakpatuhan'),
            Route::get('/kehadiran/staf-color-list')
                ->action(Web\Kehadiran\StaffAction::class)
                ->name('kehadiran-staf-color-list'),
            Route::get('/kehadiran/kad-warna')
                ->action(Web\Kehadiran\StaffAction::class)
                ->name('kehadiran-kad-warna'),
            Route::get('/kehadiran/kad_warna')
                ->action(Web\Kehadiran\StaffAction::class)
                ->name('kehadiran-kad-warna-legacy-slug'),
            Route::get('/kehadiran/wfh-list')
                ->action(Web\Kehadiran\StaffAction::class)
                ->name('kehadiran-wfh-list'),
            Route::get('/kehadiran/absent-list')
                ->action(Web\Kehadiran\StaffAction::class)
                ->name('kehadiran-absent-list'),
            Route::get('/kehadiran/senarai_tindakan')
                ->action(Web\Kehadiran\OfficerAction::class)
                ->name('kehadiran-senarai-tindakan'),
            Route::get('/kehadiran/staff-color-card')
                ->action(Web\Kehadiran\OfficerAction::class)
                ->name('kehadiran-staff-color-card'),
            Route::get('/kehadiran/pantau-kehadiran')
                ->action(Web\Kehadiran\OfficerAction::class)
                ->name('kehadiran-pantau-kehadiran'),
            Route::get('/kehadiran/monthly-summary')
                ->action(Web\Kehadiran\OfficerAction::class)
                ->name('kehadiran-monthly-summary'),
            Route::get('/kehadiran/wfh-lulus')
                ->action(Web\Kehadiran\OfficerAction::class)
                ->name('kehadiran-wfh-lulus'),
            Route::get('/kehadiran/pantau-permohonan-wfh-pegawai')
                ->action(Web\Kehadiran\OfficerAction::class)
                ->name('kehadiran-pantau-permohonan-wfh-pegawai'),
            Route::get('/kehadiran/absent-list-kp')
                ->action(Web\Kehadiran\OfficerAction::class)
                ->name('kehadiran-absent-list-kp'),
            Route::methods(['GET', 'POST'], '/kehadiran/pengesahan')
                ->action(Web\Kehadiran\WriteAction::class)
                ->name('kehadiran-pengesahan'),
            Route::methods(['GET', 'POST'], '/kehadiran/wfh-lulus-tindakan')
                ->action(Web\Kehadiran\WriteAction::class)
                ->name('kehadiran-wfh-lulus-tindakan'),
            Route::methods(['GET', 'POST'], '/kehadiran/delete-wfh')
                ->action(Web\Kehadiran\WriteAction::class)
                ->name('kehadiran-delete-wfh'),
            Route::methods(['GET', 'POST'], '/kehadiran/verify-thtc-sv')
                ->action(Web\Kehadiran\WriteAction::class)
                ->name('kehadiran-verify-thtc-sv'),
            Route::methods(['GET', 'POST'], '/kehadiran/approve-thtc')
                ->action(Web\Kehadiran\WriteAction::class)
                ->name('kehadiran-approve-thtc'),
            Route::get('/kehadiran/supervisor-staff-attendance-report')
                ->action(Web\Kehadiran\SupervisorStaffAttendanceReportAction::class)
                ->name('kehadiran-supervisor-staff-attendance-report'),
            Route::get('/kehadiran/senarai_kakitangan')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-senarai-kakitangan'),
            Route::get('/kehadiran/staff-wbb')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-staff-wbb'),
            Route::methods(['GET', 'POST'], '/kehadiran/wbb-list')
                ->action(Web\Kehadiran\WbbListAction::class)
                ->name('kehadiran-wbb-list'),
            Route::post('/kehadiran/del-wbb')
                ->action(Web\Kehadiran\WbbDeleteAction::class)
                ->name('kehadiran-del-wbb'),
            Route::methods(['GET', 'POST'], '/kehadiran/set-peg')
                ->action(Web\Kehadiran\SetPegAction::class)
                ->name('kehadiran-set-peg'),
            Route::get('/kehadiran/sup-mth-rpt')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-sup-mth-rpt'),
            Route::get('/kehadiran/sup-year-rpt')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-sup-year-rpt'),
            Route::get('/kehadiran/senarai-warna-kad')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-senarai-warna-kad'),
            Route::get('/kehadiran/sup-year-performance-rpt')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-sup-year-performance-rpt'),
            Route::get('/kehadiran/pantau-staf')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-pantau-staf'),
            Route::get('/kehadiran/shield-status-list')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-shield-status-list'),
            Route::get('/kehadiran/staff-shift-list')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-staff-shift-list'),
            Route::get('/kehadiran/shift-setup')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-shift-setup'),
            Route::get('/kehadiran-admin/manage-shift-supervisor')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-admin-manage-shift-supervisor'),
            Route::get('/kehadiran/wfh-by-mth')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-wfh-by-mth'),
            Route::methods(['GET', 'POST'], '/kehadiran/wfh-edit-day')
                ->action(Web\Kehadiran\WfhEditDayAction::class)
                ->name('kehadiran-wfh-edit-day'),
            Route::get('/kehadiran/wfh-jadual')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-wfh-jadual'),
            Route::get('/kehadiran/pantau-permohonan-wfh')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-pantau-permohonan-wfh'),
            Route::get('/kehadiran/absent-list-sv')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-absent-list-sv'),
            Route::get('/kehadiran/kj-pantau-kehadiran')
                ->action(Web\Kehadiran\HeadDepartmentAction::class)
                ->name('kehadiran-kj-pantau-kehadiran'),
            Route::get('/kehadiran/wfh-jadual-kj')
                ->action(Web\Kehadiran\HeadDepartmentAction::class)
                ->name('kehadiran-wfh-jadual-kj'),
            Route::get('/kehadiran/list-kj')
                ->action(Web\Kehadiran\HeadDepartmentAction::class)
                ->name('kehadiran-list-kj'),
            Route::get('/kehadiran/staff-all')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('kehadiran-staff-all'),
            Route::get('/kehadiran/admin-mth-rpt')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('kehadiran-admin-mth-rpt'),
            Route::get('/kehadiran/color-rpt')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('kehadiran-color-rpt'),
            Route::get('/attendance/index')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('attendance-index'),
            Route::get('/kehadiran/pantau-permohonan-wfh-admin')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('kehadiran-pantau-permohonan-wfh-admin'),
            Route::get('/kehadiran/wfh-jadual-admin')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('kehadiran-wfh-jadual-admin'),
            Route::get('/kehadiran/statistik-wfh')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('kehadiran-statistik-wfh'),
            Route::get('/kehadiran/admin-post-list')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('kehadiran-admin-post-list'),
            Route::get('/attendance/absent-list-bpsm')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('attendance-absent-list-bpsm'),
            Route::get('/cuti/individu/index')
                ->action(Web\Cuti\Individu\IndexAction::class)
                ->name('cuti-individu-index'),
            Route::get('/cuti/individu/jsoncalendar')
                ->action(Web\Cuti\Individu\JsonCalendarAction::class)
                ->name('cuti-individu-jsoncalendar'),
            Route::get('/cuti/individu/list')
                ->action(Web\Cuti\Individu\ListAction::class)
                ->name('cuti-individu-list'),
            Route::get('/cuti/individu/leave-detail')
                ->action(Web\Cuti\Individu\DetailAction::class)
                ->name('cuti-individu-leave-detail'),
            Route::get('/cuti/individu/pilih')
                ->action(Web\Cuti\Individu\PageAction::class)
                ->name('cuti-individu-pilih'),
            Route::get('/cuti/individu/ganti')
                ->action(Web\Cuti\Individu\PageAction::class)
                ->name('cuti-individu-ganti'),
            Route::get('/cuti/individu/substitute-list')
                ->action(Web\Cuti\Individu\PageAction::class)
                ->name('cuti-individu-substitute-list'),
            Route::get('/cuti/individu/statement')
                ->action(Web\Cuti\Individu\PageAction::class)
                ->name('cuti-individu-statement'),
            Route::get('/cuti/individu/entitlement')
                ->action(Web\Cuti\Individu\PageAction::class)
                ->name('cuti-individu-entitlement'),
            Route::get('/cuti/individu/gcr-list')
                ->action(Web\Cuti\Individu\PageAction::class)
                ->name('cuti-individu-gcr-list'),
            Route::methods(['GET', 'POST'], '/cuti/individu/apply-gcr')
                ->action(Web\Cuti\Individu\ApplyGcrAction::class)
                ->name('cuti-individu-apply-gcr'),
            Route::methods(['GET', 'POST'], '/cuti/individu/agree')
                ->action(Web\Cuti\Individu\AgreeAction::class)
                ->name('cuti-individu-agree'),
            Route::get('/cuti/individu/surat')
                ->action(Web\Cuti\Shared\LegacyRedirectAction::class)
                ->name('cuti-individu-surat'),
            Route::methods(['GET', 'POST'], '/cuti/individu/upload-file')
                ->action(Web\Cuti\Shared\LegacyRedirectAction::class)
                ->name('cuti-individu-upload-file'),
            Route::methods(['GET', 'POST'], '/cuti/individu/upload')
                ->action(Web\Cuti\Shared\LegacyRedirectAction::class)
                ->name('cuti-individu-upload'),
            Route::methods(['GET', 'POST'], '/cuti/individu/cancel-leave')
                ->action(Web\Cuti\Shared\LegacyRedirectAction::class)
                ->name('cuti-individu-cancel-leave'),
            Route::methods(['GET', 'POST'], '/cuti/individu/ck')
                ->action(Web\Cuti\Individu\CutiKecemasanFormAction::class)
                ->name('cuti-individu-ck'),
            Route::methods(['GET', 'POST'], '/cuti/individu/cb')
                ->action(Web\Cuti\Individu\CutiBersalinFormAction::class)
                ->name('cuti-individu-cb'),
            Route::methods(['GET', 'POST'], '/cuti/individu/cr1')
                ->action(Web\Cuti\Individu\CutiRehatDalamNegaraFormAction::class)
                ->name('cuti-individu-cr1'),
            Route::methods(['GET', 'POST'], '/cuti/individu/cr1-aca')
                ->action(Web\Cuti\Individu\CutiRehatDalamNegaraAkademikFormAction::class)
                ->name('cuti-individu-cr1-aca'),
            Route::methods(['GET', 'POST'], '/cuti/individu/cr2')
                ->action(Web\Cuti\Individu\CutiRehatLuarNegaraFormAction::class)
                ->name('cuti-individu-cr2'),
            Route::methods(['GET', 'POST'], '/cuti/individu/ctr')
                ->action(Web\Cuti\Individu\CutiTanpaRekodFormAction::class)
                ->name('cuti-individu-ctr'),
            Route::methods(['GET', 'POST'], '/cuti/individu/cs1')
                ->action(Web\Cuti\Individu\CutiSakitFormAction::class)
                ->name('cuti-individu-cs1'),
            Route::methods(['GET', 'POST'], '/cuti/individu/cs2')
                ->action(Web\Cuti\Individu\CutiSakitLanjutanFormAction::class)
                ->name('cuti-individu-cs2'),
            Route::methods(['GET', 'POST'], '/cuti/individu/hso')
                ->action(Web\Cuti\Individu\HsoFormAction::class)
                ->name('cuti-individu-hso'),
            Route::get('/cuti/pegawai/senarai-peraku')
                ->action(Web\Cuti\Pegawai\OfficerAction::class)
                ->name('cuti-pegawai-senarai-peraku'),
            Route::get('/cuti/pegawai/senarai-lulus')
                ->action(Web\Cuti\Pegawai\OfficerAction::class)
                ->name('cuti-pegawai-senarai-lulus'),
            Route::get('/cuti/pegawai/gcr-list-checked')
                ->action(Web\Cuti\Pegawai\OfficerAction::class)
                ->name('cuti-pegawai-gcr-list-checked'),
            Route::get('/cuti/pegawai/list')
                ->action(Web\Cuti\Pegawai\OfficerAction::class)
                ->name('cuti-pegawai-list'),
            Route::get('/cuti/pegawai/staff-leave')
                ->action(Web\Cuti\Pegawai\OfficerAction::class)
                ->name('cuti-pegawai-staff-leave'),
            Route::get('/cuti/pegawai/sort')
                ->action(Web\Cuti\Pegawai\OfficerAction::class)
                ->name('cuti-pegawai-sort'),
            Route::get('/cuti/pegawai/bod-list')
                ->action(Web\Cuti\Pegawai\OfficerAction::class)
                ->name('cuti-pegawai-bod-list'),
            Route::get('/cuti/pegawai/report')
                ->action(Web\Cuti\Pentadbiran\HsoAction::class)
                ->name('cuti-pegawai-report'),
            Route::get('/manage/list')
                ->action(Web\Cuti\Manage\ManageAction::class)
                ->name('manage-list'),
            Route::get('/cuti/supervisor/sick-leave-list')
                ->action(Web\Cuti\Supervisor\SupervisorAction::class)
                ->name('cuti-supervisor-sick-leave-list'),
            Route::get('/cuti/supervisor/supervised-staff')
                ->action(Web\Cuti\Supervisor\SupervisorAction::class)
                ->name('cuti-supervisor-supervised-staff'),
            Route::get('/cuti/supervisor/leave-statement-report')
                ->action(Web\Cuti\Supervisor\SupervisorAction::class)
                ->name('cuti-supervisor-leave-statement-report'),
            Route::get('/cuti/supervisor/leave-monitoring')
                ->action(Web\Cuti\Supervisor\SupervisorAction::class)
                ->name('cuti-supervisor-leave-monitoring'),
            Route::get('/cuti/supervisor/calender')
                ->action(Web\Cuti\Supervisor\SupervisorAction::class)
                ->name('cuti-supervisor-calender'),
            Route::get('/cuti/supervisor/gcr-list')
                ->action(Web\Cuti\Supervisor\SupervisorAction::class)
                ->name('cuti-supervisor-gcr-list'),
            Route::get('/cuti/supervisor/gcr-list-checked')
                ->action(Web\Cuti\Supervisor\SupervisorAction::class)
                ->name('cuti-supervisor-gcr-list-checked'),
            Route::get('/cuti/observer/my-staff')
                ->action(Web\Cuti\Observer\ObserverAction::class)
                ->name('cuti-observer-my-staff'),
            Route::get('/cuti/admin/cr2-list')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-cr2-list'),
            Route::get('/cuti/admin/behalf-list')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-behalf-list'),
            Route::get('/cuti/admin/access')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-access'),
            Route::get('/cuti/admin/gcr-application-list')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-gcr-application-list'),
            Route::get('/cuti/admin/open-application')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-open-application'),
            Route::get('/cuti/admin/ph-list')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-ph-list'),
            Route::get('/cuti/admin/cb-list')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-cb-list'),
            Route::get('/cuti/admin/admin-index')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-admin-index'),
            Route::get('/cuti/admin/staff-list')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-staff-list'),
            Route::get('/cuti/admin/report')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-report'),
            Route::get('/cuti/admin/report-leave-list')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-report-leave-list'),
            Route::get('/perkhidmatan/index')
                ->action(Web\Perkhidmatan\IndexAction::class)
                ->name('perkhidmatan-index'),
            Route::get('/perkhidmatan/view')
                ->action(Web\Perkhidmatan\ViewAction::class)
                ->name('perkhidmatan-view'),
            Route::get('/perkhidmatan/viewuser')
                ->action(Web\Perkhidmatan\SelfViewAction::class)
                ->name('perkhidmatan-viewuser'),
            Route::get('/perkhidmatan/redirectview')
                ->action(Web\Perkhidmatan\RedirectViewAction::class)
                ->name('perkhidmatan-redirectview'),
            Route::get('/perkhidmatan/tree-stat')
                ->action(Web\Perkhidmatan\TimelineAction::class)
                ->name('perkhidmatan-tree-stat'),
            Route::get('/perkhidmatan/status-lantikan/view')
                ->action(Web\Perkhidmatan\Sections\StatusLantikan\ViewAction::class)
                ->name('perkhidmatan-status-lantikan-view'),
            Route::get('/perkhidmatan/status-lantikan/viewuser')
                ->action(Web\Perkhidmatan\Sections\StatusLantikan\ViewAction::class)
                ->name('perkhidmatan-status-lantikan-viewuser'),
            Route::get('/perkhidmatan/status-lantikan/lihat')
                ->action(Web\Perkhidmatan\Sections\StatusLantikan\DetailAction::class)
                ->name('perkhidmatan-status-lantikan-lihat'),
            Route::get('/perkhidmatan/status-sandangan/view')
                ->action(Web\Perkhidmatan\Sections\StatusSandangan\ViewAction::class)
                ->name('perkhidmatan-status-sandangan-view'),
            Route::get('/perkhidmatan/status-sandangan/viewuser')
                ->action(Web\Perkhidmatan\Sections\StatusSandangan\ViewAction::class)
                ->name('perkhidmatan-status-sandangan-viewuser'),
            Route::get('/perkhidmatan/status-sandangan/lihat')
                ->action(Web\Perkhidmatan\Sections\StatusSandangan\DetailAction::class)
                ->name('perkhidmatan-status-sandangan-lihat'),
            Route::get('/perkhidmatan/status-perkhidmatan/view')
                ->action(Web\Perkhidmatan\Sections\StatusPerkhidmatan\ViewAction::class)
                ->name('perkhidmatan-status-perkhidmatan-view'),
            Route::get('/perkhidmatan/status-perkhidmatan/viewuser')
                ->action(Web\Perkhidmatan\Sections\StatusPerkhidmatan\ViewAction::class)
                ->name('perkhidmatan-status-perkhidmatan-viewuser'),
            Route::get('/perkhidmatan/status-perkhidmatan/lihat')
                ->action(Web\Perkhidmatan\Sections\StatusPerkhidmatan\DetailAction::class)
                ->name('perkhidmatan-status-perkhidmatan-lihat'),
            Route::get('/perkhidmatan/pihak-berkuasa/view')
                ->action(Web\Perkhidmatan\Sections\PihakBerkuasa\ViewAction::class)
                ->name('perkhidmatan-pihak-berkuasa-view'),
            Route::get('/perkhidmatan/pihak-berkuasa/viewuser')
                ->action(Web\Perkhidmatan\Sections\PihakBerkuasa\ViewAction::class)
                ->name('perkhidmatan-pihak-berkuasa-viewuser'),
            Route::get('/perkhidmatan/pihak-berkuasa/lihat')
                ->action(Web\Perkhidmatan\Sections\PihakBerkuasa\DetailAction::class)
                ->name('perkhidmatan-pihak-berkuasa-lihat'),
            Route::get('/perkhidmatan/beban-perkhidmatan/view')
                ->action(Web\Perkhidmatan\Sections\BebanPerkhidmatan\ViewAction::class)
                ->name('perkhidmatan-beban-perkhidmatan-view'),
            Route::get('/perkhidmatan/beban-perkhidmatan/viewuser')
                ->action(Web\Perkhidmatan\Sections\BebanPerkhidmatan\ViewAction::class)
                ->name('perkhidmatan-beban-perkhidmatan-viewuser'),
            Route::get('/perkhidmatan/beban-perkhidmatan/lihat')
                ->action(Web\Perkhidmatan\Sections\BebanPerkhidmatan\DetailAction::class)
                ->name('perkhidmatan-beban-perkhidmatan-lihat'),
            Route::get('/perkhidmatan/waktu-bekerja/view')
                ->action(Web\Perkhidmatan\Sections\WaktuBekerja\ViewAction::class)
                ->name('perkhidmatan-waktu-bekerja-view'),
            Route::get('/perkhidmatan/waktu-bekerja/viewuser')
                ->action(Web\Perkhidmatan\Sections\WaktuBekerja\ViewAction::class)
                ->name('perkhidmatan-waktu-bekerja-viewuser'),
            Route::get('/perkhidmatan/waktu-bekerja/lihat')
                ->action(Web\Perkhidmatan\Sections\WaktuBekerja\DetailAction::class)
                ->name('perkhidmatan-waktu-bekerja-lihat'),
            Route::get('/perkhidmatan/status-pengesahan/view')
                ->action(Web\Perkhidmatan\Sections\StatusPengesahan\ViewAction::class)
                ->name('perkhidmatan-status-pengesahan-view'),
            Route::get('/perkhidmatan/status-pengesahan/viewuser')
                ->action(Web\Perkhidmatan\Sections\StatusPengesahan\ViewAction::class)
                ->name('perkhidmatan-status-pengesahan-viewuser'),
            Route::get('/perkhidmatan/status-pengesahan/lihat')
                ->action(Web\Perkhidmatan\Sections\StatusPengesahan\DetailAction::class)
                ->name('perkhidmatan-status-pengesahan-lihat'),
            Route::get('/perkhidmatan/tempoh-percubaan/view')
                ->action(Web\Perkhidmatan\Sections\TempohPercubaan\ViewAction::class)
                ->name('perkhidmatan-tempoh-percubaan-view'),
            Route::get('/perkhidmatan/tempoh-percubaan/viewuser')
                ->action(Web\Perkhidmatan\Sections\TempohPercubaan\ViewAction::class)
                ->name('perkhidmatan-tempoh-percubaan-viewuser'),
            Route::get('/perkhidmatan/tempoh-percubaan/lihat')
                ->action(Web\Perkhidmatan\Sections\TempohPercubaan\DetailAction::class)
                ->name('perkhidmatan-tempoh-percubaan-lihat'),
            Route::get('/perkhidmatan/jenis-gaji/view')
                ->action(Web\Perkhidmatan\Sections\JenisGaji\ViewAction::class)
                ->name('perkhidmatan-jenis-gaji-view'),
            Route::get('/perkhidmatan/jenis-gaji/viewuser')
                ->action(Web\Perkhidmatan\Sections\JenisGaji\ViewAction::class)
                ->name('perkhidmatan-jenis-gaji-viewuser'),
            Route::get('/perkhidmatan/jenis-gaji/lihat')
                ->action(Web\Perkhidmatan\Sections\JenisGaji\DetailAction::class)
                ->name('perkhidmatan-jenis-gaji-lihat'),
            Route::get('/perkhidmatan/jenis-perkhidmatan/view')
                ->action(Web\Perkhidmatan\Sections\JenisPerkhidmatan\ViewAction::class)
                ->name('perkhidmatan-jenis-perkhidmatan-view'),
            Route::get('/perkhidmatan/jenis-perkhidmatan/viewuser')
                ->action(Web\Perkhidmatan\Sections\JenisPerkhidmatan\ViewAction::class)
                ->name('perkhidmatan-jenis-perkhidmatan-viewuser'),
            Route::get('/perkhidmatan/jenis-perkhidmatan/lihat')
                ->action(Web\Perkhidmatan\Sections\JenisPerkhidmatan\DetailAction::class)
                ->name('perkhidmatan-jenis-perkhidmatan-lihat'),
            Route::get('/perkhidmatan/pergerakan-gaji/view')
                ->action(Web\Perkhidmatan\Sections\PergerakanGaji\ViewAction::class)
                ->name('perkhidmatan-pergerakan-gaji-view'),
            Route::get('/perkhidmatan/pergerakan-gaji/viewuser')
                ->action(Web\Perkhidmatan\Sections\PergerakanGaji\ViewAction::class)
                ->name('perkhidmatan-pergerakan-gaji-viewuser'),
            Route::get('/perkhidmatan/pergerakan-gaji/lihat')
                ->action(Web\Perkhidmatan\Sections\PergerakanGaji\DetailAction::class)
                ->name('perkhidmatan-pergerakan-gaji-lihat'),
            Route::get('/perkhidmatan/fail-perkhidmatan/view')
                ->action(Web\Perkhidmatan\Sections\FailPerkhidmatan\ViewAction::class)
                ->name('perkhidmatan-fail-perkhidmatan-view'),
            Route::get('/perkhidmatan/fail-perkhidmatan/viewuser')
                ->action(Web\Perkhidmatan\Sections\FailPerkhidmatan\ViewAction::class)
                ->name('perkhidmatan-fail-perkhidmatan-viewuser'),
            Route::get('/perkhidmatan/fail-perkhidmatan/lihat')
                ->action(Web\Perkhidmatan\Sections\FailPerkhidmatan\DetailAction::class)
                ->name('perkhidmatan-fail-perkhidmatan-lihat'),
            Route::get('/perkhidmatan/status-pencen/view')
                ->action(Web\Perkhidmatan\Sections\StatusPencen\ViewAction::class)
                ->name('perkhidmatan-status-pencen-view'),
            Route::get('/perkhidmatan/status-pencen/viewuser')
                ->action(Web\Perkhidmatan\Sections\StatusPencen\ViewAction::class)
                ->name('perkhidmatan-status-pencen-viewuser'),
            Route::get('/perkhidmatan/status-pencen/lihat')
                ->action(Web\Perkhidmatan\Sections\StatusPencen\DetailAction::class)
                ->name('perkhidmatan-status-pencen-lihat'),
            Route::get('/perkhidmatan/mata-gaji/view')
                ->action(Web\Perkhidmatan\Sections\MataGaji\ViewAction::class)
                ->name('perkhidmatan-mata-gaji-view'),
            Route::get('/perkhidmatan/mata-gaji/viewuser')
                ->action(Web\Perkhidmatan\Sections\MataGaji\ViewAction::class)
                ->name('perkhidmatan-mata-gaji-viewuser'),
            Route::get('/perkhidmatan/mata-gaji/lihat')
                ->action(Web\Perkhidmatan\Sections\MataGaji\DetailAction::class)
                ->name('perkhidmatan-mata-gaji-lihat'),
            Route::get('/perkhidmatan/pihak-berkuasa-pencen/view')
                ->action(Web\Perkhidmatan\Sections\PihakBerkuasaPencen\ViewAction::class)
                ->name('perkhidmatan-pihak-berkuasa-pencen-view'),
            Route::get('/perkhidmatan/pihak-berkuasa-pencen/viewuser')
                ->action(Web\Perkhidmatan\Sections\PihakBerkuasaPencen\ViewAction::class)
                ->name('perkhidmatan-pihak-berkuasa-pencen-viewuser'),
            Route::get('/perkhidmatan/pihak-berkuasa-pencen/lihat')
                ->action(Web\Perkhidmatan\Sections\PihakBerkuasaPencen\DetailAction::class)
                ->name('perkhidmatan-pihak-berkuasa-pencen-lihat'),
            Route::get('/perkhidmatan/umur-bersara/view')
                ->action(Web\Perkhidmatan\Sections\UmurBersara\ViewAction::class)
                ->name('perkhidmatan-umur-bersara-view'),
            Route::get('/perkhidmatan/umur-bersara/viewuser')
                ->action(Web\Perkhidmatan\Sections\UmurBersara\ViewAction::class)
                ->name('perkhidmatan-umur-bersara-viewuser'),
            Route::get('/perkhidmatan/umur-bersara/lihat')
                ->action(Web\Perkhidmatan\Sections\UmurBersara\DetailAction::class)
                ->name('perkhidmatan-umur-bersara-lihat'),
            Route::get('/perkhidmatan/sejarah-penempatan/view')
                ->action(Web\Perkhidmatan\Sections\SejarahPenempatan\ViewAction::class)
                ->name('perkhidmatan-sejarah-penempatan-view'),
            Route::get('/perkhidmatan/sejarah-penempatan/viewuser')
                ->action(Web\Perkhidmatan\Sections\SejarahPenempatan\ViewAction::class)
                ->name('perkhidmatan-sejarah-penempatan-viewuser'),
            Route::get('/perkhidmatan/sejarah-penempatan/lihat')
                ->action(Web\Perkhidmatan\Sections\SejarahPenempatan\DetailAction::class)
                ->name('perkhidmatan-sejarah-penempatan-lihat'),
            Route::get('/perkhidmatan/perubahan-data/view')
                ->action(Web\Perkhidmatan\Sections\PerubahanData\ViewAction::class)
                ->name('perkhidmatan-perubahan-data-view'),
            Route::get('/perkhidmatan/perubahan-data/viewuser')
                ->action(Web\Perkhidmatan\Sections\PerubahanData\ViewAction::class)
                ->name('perkhidmatan-perubahan-data-viewuser'),
            Route::get('/perkhidmatan/perubahan-data/lihat')
                ->action(Web\Perkhidmatan\Sections\PerubahanData\DetailAction::class)
                ->name('perkhidmatan-perubahan-data-lihat'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/status-lantikan/tambah')
                ->action(Web\Perkhidmatan\Sections\StatusLantikan\FormAction::class)
                ->name('perkhidmatan-status-lantikan-tambah'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/status-lantikan/kemaskini')
                ->action(Web\Perkhidmatan\Sections\StatusLantikan\FormAction::class)
                ->name('perkhidmatan-status-lantikan-kemaskini'),
            Route::post('/perkhidmatan/status-lantikan/padam')
                ->action(Web\Perkhidmatan\Sections\StatusLantikan\DeleteAction::class)
                ->name('perkhidmatan-status-lantikan-padam'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/status-perkhidmatan/tambah')
                ->action(Web\Perkhidmatan\Sections\StatusPerkhidmatan\FormAction::class)
                ->name('perkhidmatan-status-perkhidmatan-tambah'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/status-perkhidmatan/kemaskini')
                ->action(Web\Perkhidmatan\Sections\StatusPerkhidmatan\FormAction::class)
                ->name('perkhidmatan-status-perkhidmatan-kemaskini'),
            Route::post('/perkhidmatan/status-perkhidmatan/padam')
                ->action(Web\Perkhidmatan\Sections\StatusPerkhidmatan\DeleteAction::class)
                ->name('perkhidmatan-status-perkhidmatan-padam'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/pihak-berkuasa/tambah')
                ->action(Web\Perkhidmatan\Sections\PihakBerkuasa\FormAction::class)
                ->name('perkhidmatan-pihak-berkuasa-tambah'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/pihak-berkuasa/kemaskini')
                ->action(Web\Perkhidmatan\Sections\PihakBerkuasa\FormAction::class)
                ->name('perkhidmatan-pihak-berkuasa-kemaskini'),
            Route::post('/perkhidmatan/pihak-berkuasa/padam')
                ->action(Web\Perkhidmatan\Sections\PihakBerkuasa\DeleteAction::class)
                ->name('perkhidmatan-pihak-berkuasa-padam'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/beban-perkhidmatan/tambah')
                ->action(Web\Perkhidmatan\Sections\BebanPerkhidmatan\FormAction::class)
                ->name('perkhidmatan-beban-perkhidmatan-tambah'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/beban-perkhidmatan/kemaskini')
                ->action(Web\Perkhidmatan\Sections\BebanPerkhidmatan\FormAction::class)
                ->name('perkhidmatan-beban-perkhidmatan-kemaskini'),
            Route::post('/perkhidmatan/beban-perkhidmatan/padam')
                ->action(Web\Perkhidmatan\Sections\BebanPerkhidmatan\DeleteAction::class)
                ->name('perkhidmatan-beban-perkhidmatan-padam'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/status-pengesahan/tambah')
                ->action(Web\Perkhidmatan\Sections\StatusPengesahan\FormAction::class)
                ->name('perkhidmatan-status-pengesahan-tambah'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/status-pengesahan/kemaskini')
                ->action(Web\Perkhidmatan\Sections\StatusPengesahan\FormAction::class)
                ->name('perkhidmatan-status-pengesahan-kemaskini'),
            Route::post('/perkhidmatan/status-pengesahan/padam')
                ->action(Web\Perkhidmatan\Sections\StatusPengesahan\DeleteAction::class)
                ->name('perkhidmatan-status-pengesahan-padam'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/tempoh-percubaan/tambah')
                ->action(Web\Perkhidmatan\Sections\TempohPercubaan\FormAction::class)
                ->name('perkhidmatan-tempoh-percubaan-tambah'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/tempoh-percubaan/kemaskini')
                ->action(Web\Perkhidmatan\Sections\TempohPercubaan\FormAction::class)
                ->name('perkhidmatan-tempoh-percubaan-kemaskini'),
            Route::post('/perkhidmatan/tempoh-percubaan/padam')
                ->action(Web\Perkhidmatan\Sections\TempohPercubaan\DeleteAction::class)
                ->name('perkhidmatan-tempoh-percubaan-padam'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/jenis-gaji/tambah')
                ->action(Web\Perkhidmatan\Sections\JenisGaji\FormAction::class)
                ->name('perkhidmatan-jenis-gaji-tambah'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/jenis-gaji/kemaskini')
                ->action(Web\Perkhidmatan\Sections\JenisGaji\FormAction::class)
                ->name('perkhidmatan-jenis-gaji-kemaskini'),
            Route::post('/perkhidmatan/jenis-gaji/padam')
                ->action(Web\Perkhidmatan\Sections\JenisGaji\DeleteAction::class)
                ->name('perkhidmatan-jenis-gaji-padam'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/jenis-perkhidmatan/tambah')
                ->action(Web\Perkhidmatan\Sections\JenisPerkhidmatan\FormAction::class)
                ->name('perkhidmatan-jenis-perkhidmatan-tambah'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/jenis-perkhidmatan/kemaskini')
                ->action(Web\Perkhidmatan\Sections\JenisPerkhidmatan\FormAction::class)
                ->name('perkhidmatan-jenis-perkhidmatan-kemaskini'),
            Route::post('/perkhidmatan/jenis-perkhidmatan/padam')
                ->action(Web\Perkhidmatan\Sections\JenisPerkhidmatan\DeleteAction::class)
                ->name('perkhidmatan-jenis-perkhidmatan-padam'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/pergerakan-gaji/tambah')
                ->action(Web\Perkhidmatan\Sections\PergerakanGaji\FormAction::class)
                ->name('perkhidmatan-pergerakan-gaji-tambah'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/pergerakan-gaji/kemaskini')
                ->action(Web\Perkhidmatan\Sections\PergerakanGaji\FormAction::class)
                ->name('perkhidmatan-pergerakan-gaji-kemaskini'),
            Route::post('/perkhidmatan/pergerakan-gaji/padam')
                ->action(Web\Perkhidmatan\Sections\PergerakanGaji\DeleteAction::class)
                ->name('perkhidmatan-pergerakan-gaji-padam'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/fail-perkhidmatan/tambah')
                ->action(Web\Perkhidmatan\Sections\FailPerkhidmatan\FormAction::class)
                ->name('perkhidmatan-fail-perkhidmatan-tambah'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/fail-perkhidmatan/kemaskini')
                ->action(Web\Perkhidmatan\Sections\FailPerkhidmatan\FormAction::class)
                ->name('perkhidmatan-fail-perkhidmatan-kemaskini'),
            Route::post('/perkhidmatan/fail-perkhidmatan/padam')
                ->action(Web\Perkhidmatan\Sections\FailPerkhidmatan\DeleteAction::class)
                ->name('perkhidmatan-fail-perkhidmatan-padam'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/status-pencen/tambah')
                ->action(Web\Perkhidmatan\Sections\StatusPencen\FormAction::class)
                ->name('perkhidmatan-status-pencen-tambah'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/status-pencen/kemaskini')
                ->action(Web\Perkhidmatan\Sections\StatusPencen\FormAction::class)
                ->name('perkhidmatan-status-pencen-kemaskini'),
            Route::post('/perkhidmatan/status-pencen/padam')
                ->action(Web\Perkhidmatan\Sections\StatusPencen\DeleteAction::class)
                ->name('perkhidmatan-status-pencen-padam'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/mata-gaji/tambah')
                ->action(Web\Perkhidmatan\Sections\MataGaji\FormAction::class)
                ->name('perkhidmatan-mata-gaji-tambah'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/mata-gaji/kemaskini')
                ->action(Web\Perkhidmatan\Sections\MataGaji\FormAction::class)
                ->name('perkhidmatan-mata-gaji-kemaskini'),
            Route::post('/perkhidmatan/mata-gaji/padam')
                ->action(Web\Perkhidmatan\Sections\MataGaji\DeleteAction::class)
                ->name('perkhidmatan-mata-gaji-padam'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/pihak-berkuasa-pencen/tambah')
                ->action(Web\Perkhidmatan\Sections\PihakBerkuasaPencen\FormAction::class)
                ->name('perkhidmatan-pihak-berkuasa-pencen-tambah'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/pihak-berkuasa-pencen/kemaskini')
                ->action(Web\Perkhidmatan\Sections\PihakBerkuasaPencen\FormAction::class)
                ->name('perkhidmatan-pihak-berkuasa-pencen-kemaskini'),
            Route::post('/perkhidmatan/pihak-berkuasa-pencen/padam')
                ->action(Web\Perkhidmatan\Sections\PihakBerkuasaPencen\DeleteAction::class)
                ->name('perkhidmatan-pihak-berkuasa-pencen-padam'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/umur-bersara/tambah')
                ->action(Web\Perkhidmatan\Sections\UmurBersara\FormAction::class)
                ->name('perkhidmatan-umur-bersara-tambah'),
            Route::methods(['GET', 'POST'], '/perkhidmatan/umur-bersara/kemaskini')
                ->action(Web\Perkhidmatan\Sections\UmurBersara\FormAction::class)
                ->name('perkhidmatan-umur-bersara-kemaskini'),
            Route::post('/perkhidmatan/umur-bersara/padam')
                ->action(Web\Perkhidmatan\Sections\UmurBersara\DeleteAction::class)
                ->name('perkhidmatan-umur-bersara-padam'),
            Route::get('/biodata/index')
                ->action(Web\Biodata\IndexAction::class)
                ->name('biodata-index'),
            Route::get('/biodata/userview')
                ->action(Web\Biodata\ProfileAction::class)
                ->name('biodata-userview'),
            Route::get('/biodata/adminview')
                ->action(Web\Biodata\ProfileAction::class)
                ->name('biodata-adminview'),
            Route::get('/biodata/lihatbiodata')
                ->action(Web\Biodata\DetailAction::class)
                ->name('biodata-lihatbiodata'),
            Route::get('/biodata/lihatbiodatakakitangan')
                ->action(Web\Biodata\DetailAction::class)
                ->name('biodata-lihatbiodatakakitangan'),
            Route::get('/biodata/ketua-program-view')
                ->action(Web\Biodata\ProfileAction::class)
                ->name('biodata-ketua-program-view'),
            Route::get('/biodata/view-a-r-p')
                ->action(Web\Biodata\ProfileAction::class)
                ->name('biodata-view-a-r-p'),
            Route::get('/biodata/penetapanpengguna')
                ->action(Web\Biodata\SettingsAction::class)
                ->name('biodata-penetapanpengguna'),
            Route::get('/biodata/keahlian/view')
                ->action(Web\Biodata\Sections\Keahlian\ViewAction::class)
                ->name('biodata-keahlian-view'),
            Route::get('/biodata/keahlian/adminview')
                ->action(Web\Biodata\Sections\Keahlian\ViewAction::class)
                ->name('biodata-keahlian-adminview'),
            Route::methods(['GET', 'POST'], '/biodata/keahlian/tambahbadanprofesional')
                ->action(Web\Biodata\Sections\Keahlian\FormAction::class)
                ->name('biodata-keahlian-tambahbadanprofesional'),
            Route::get('/biodata/keahlian/lihatbadanprofesional')
                ->action(Web\Biodata\Sections\Keahlian\DetailAction::class)
                ->name('biodata-keahlian-lihatbadanprofesional'),
            Route::methods(['GET', 'POST'], '/biodata/keahlian/update')
                ->action(Web\Biodata\Sections\Keahlian\FormAction::class)
                ->name('biodata-keahlian-update'),
            Route::post('/biodata/keahlian/delete')
                ->action(Web\Biodata\Sections\Keahlian\DeleteAction::class)
                ->name('biodata-keahlian-delete'),
            Route::get('/biodata/alamat/view')
                ->action(Web\Biodata\Sections\Alamat\ViewAction::class)
                ->name('biodata-alamat-view'),
            Route::get('/biodata/alamat/adminview')
                ->action(Web\Biodata\Sections\Alamat\ViewAction::class)
                ->name('biodata-alamat-adminview'),
            Route::get('/biodata/alamat/lihatalamat')
                ->action(Web\Biodata\Sections\Alamat\DetailAction::class)
                ->name('biodata-alamat-lihatalamat'),
            Route::methods(['GET', 'POST'], '/biodata/alamat/tambahalamat')
                ->action(Web\Biodata\Sections\Alamat\FormAction::class)
                ->name('biodata-alamat-tambahalamat'),
            Route::methods(['GET', 'POST'], '/biodata/alamat/update')
                ->action(Web\Biodata\Sections\Alamat\FormAction::class)
                ->name('biodata-alamat-update'),
            Route::post('/biodata/alamat/delete')
                ->action(Web\Biodata\Sections\Alamat\DeleteAction::class)
                ->name('biodata-alamat-delete'),
            Route::methods(['GET', 'POST'], '/biodata/alamat/statelist')
                ->action(Web\Biodata\Sections\Alamat\JsonListAction::class)
                ->name('biodata-alamat-statelist'),
            Route::methods(['GET', 'POST'], '/biodata/alamat/citylist')
                ->action(Web\Biodata\Sections\Alamat\JsonListAction::class)
                ->name('biodata-alamat-citylist'),
            Route::get('/biodata/pendidikan/view')
                ->action(Web\Biodata\Sections\Pendidikan\ViewAction::class)
                ->name('biodata-pendidikan-view'),
            Route::get('/biodata/pendidikan/adminview')
                ->action(Web\Biodata\Sections\Pendidikan\ViewAction::class)
                ->name('biodata-pendidikan-adminview'),
            Route::get('/biodata/pendidikan/lihatpendidikan')
                ->action(Web\Biodata\Sections\Pendidikan\DetailAction::class)
                ->name('biodata-pendidikan-lihatpendidikan'),
            Route::methods(['GET', 'POST'], '/biodata/pendidikan/tambahpendidikan')
                ->action(Web\Biodata\Sections\Pendidikan\FormAction::class)
                ->name('biodata-pendidikan-tambahpendidikan'),
            Route::methods(['GET', 'POST'], '/biodata/pendidikan/update')
                ->action(Web\Biodata\Sections\Pendidikan\FormAction::class)
                ->name('biodata-pendidikan-update'),
            Route::post('/biodata/pendidikan/delete')
                ->action(Web\Biodata\Sections\Pendidikan\DeleteAction::class)
                ->name('biodata-pendidikan-delete'),
            Route::get('/biodata/pasport-permit/view')
                ->action(Web\Biodata\Sections\PasportPermit\ViewAction::class)
                ->name('biodata-pasport-permit-view'),
            Route::get('/biodata/pasport-permit/adminview')
                ->action(Web\Biodata\Sections\PasportPermit\ViewAction::class)
                ->name('biodata-pasport-permit-adminview'),
            Route::get('/biodata/pasport-permit/lihatpasport')
                ->action(Web\Biodata\Sections\PasportPermit\DetailAction::class)
                ->name('biodata-pasport-permit-lihatpasport'),
            Route::get('/biodata/pasport-permit/lihatpermitkerja')
                ->action(Web\Biodata\Sections\PasportPermit\DetailAction::class)
                ->name('biodata-pasport-permit-lihatpermitkerja'),
            Route::methods(['GET', 'POST'], '/biodata/pasport-permit/tambahpasport')
                ->action(Web\Biodata\Sections\PasportPermit\FormAction::class)
                ->name('biodata-pasport-permit-tambahpasport'),
            Route::methods(['GET', 'POST'], '/biodata/pasport-permit/tambahpermitkerja')
                ->action(Web\Biodata\Sections\PasportPermit\FormAction::class)
                ->name('biodata-pasport-permit-tambahpermitkerja'),
            Route::methods(['GET', 'POST'], '/biodata/pasport-permit/updatepasport')
                ->action(Web\Biodata\Sections\PasportPermit\FormAction::class)
                ->name('biodata-pasport-permit-updatepasport'),
            Route::methods(['GET', 'POST'], '/biodata/pasport-permit/updatepermitkerja')
                ->action(Web\Biodata\Sections\PasportPermit\FormAction::class)
                ->name('biodata-pasport-permit-updatepermitkerja'),
            Route::post('/biodata/pasport-permit/deletepaspot')
                ->action(Web\Biodata\Sections\PasportPermit\DeleteAction::class)
                ->name('biodata-pasport-permit-deletepaspot'),
            Route::post('/biodata/pasport-permit/deletepermit')
                ->action(Web\Biodata\Sections\PasportPermit\DeleteAction::class)
                ->name('biodata-pasport-permit-deletepermit'),
            Route::get('/biodata/lesen/view')
                ->action(Web\Biodata\Sections\Lesen\ViewAction::class)
                ->name('biodata-lesen-view'),
            Route::get('/biodata/lesen/adminview')
                ->action(Web\Biodata\Sections\Lesen\ViewAction::class)
                ->name('biodata-lesen-adminview'),
            Route::get('/biodata/lesen/lihatlesen')
                ->action(Web\Biodata\Sections\Lesen\DetailAction::class)
                ->name('biodata-lesen-lihatlesen'),
            Route::methods(['GET', 'POST'], '/biodata/lesen/tambahlesen')
                ->action(Web\Biodata\Sections\Lesen\FormAction::class)
                ->name('biodata-lesen-tambahlesen'),
            Route::methods(['GET', 'POST'], '/biodata/lesen/update')
                ->action(Web\Biodata\Sections\Lesen\FormAction::class)
                ->name('biodata-lesen-update'),
            Route::get('/biodata/akaun/senarai-akaun')
                ->action(Web\Biodata\Sections\Akaun\ViewAction::class)
                ->name('biodata-akaun-senarai-akaun'),
            Route::get('/biodata/akaun/admin-senarai-akaun')
                ->action(Web\Biodata\Sections\Akaun\ViewAction::class)
                ->name('biodata-akaun-admin-senarai-akaun'),
            Route::get('/biodata/akaun/lihat')
                ->action(Web\Biodata\Sections\Akaun\DetailAction::class)
                ->name('biodata-akaun-lihat'),
            Route::get('/biodata/akaun/permohonan-penukaran-akaun-gaji')
                ->action(Web\Biodata\Sections\Akaun\RequestListAction::class)
                ->name('biodata-akaun-permohonan-penukaran-akaun-gaji'),
            Route::methods(['GET', 'POST'], '/biodata/akaun/mohon-tukar-akaun')
                ->action(Web\Biodata\Sections\Akaun\RequestFormAction::class)
                ->name('biodata-akaun-mohon-tukar-akaun'),
            Route::get('/biodata/akaun/lihat-acc')
                ->action(Web\Biodata\Sections\Akaun\RequestDetailAction::class)
                ->name('biodata-akaun-lihat-acc'),
            Route::methods(['GET', 'POST'], '/biodata/akaun/admin-tambah')
                ->action(Web\Biodata\Sections\Akaun\FormAction::class)
                ->name('biodata-akaun-admin-tambah'),
            Route::methods(['GET', 'POST'], '/biodata/akaun/admin-kemaskini')
                ->action(Web\Biodata\Sections\Akaun\FormAction::class)
                ->name('biodata-akaun-admin-kemaskini'),
            Route::get('/biodata/kecacatan/view')
                ->action(Web\Biodata\Sections\Kecacatan\ViewAction::class)
                ->name('biodata-kecacatan-view'),
            Route::get('/biodata/kecacatan/adminview')
                ->action(Web\Biodata\Sections\Kecacatan\ViewAction::class)
                ->name('biodata-kecacatan-adminview'),
            Route::get('/biodata/kecacatan/lihatkecacatan')
                ->action(Web\Biodata\Sections\Kecacatan\DetailAction::class)
                ->name('biodata-kecacatan-lihatkecacatan'),
            Route::methods(['GET', 'POST'], '/biodata/kecacatan/tambahkecacatan')
                ->action(Web\Biodata\Sections\Kecacatan\FormAction::class)
                ->name('biodata-kecacatan-tambahkecacatan'),
            Route::methods(['GET', 'POST'], '/biodata/kecacatan/update')
                ->action(Web\Biodata\Sections\Kecacatan\FormAction::class)
                ->name('biodata-kecacatan-update'),
            Route::post('/biodata/kecacatan/delete')
                ->action(Web\Biodata\Sections\Kecacatan\DeleteAction::class)
                ->name('biodata-kecacatan-delete'),
            Route::get('/biodata/keluarga/view')
                ->action(Web\Biodata\Sections\Keluarga\ViewAction::class)
                ->name('biodata-keluarga-view'),
            Route::get('/biodata/keluarga/adminview')
                ->action(Web\Biodata\Sections\Keluarga\ViewAction::class)
                ->name('biodata-keluarga-adminview'),
            Route::get('/biodata/keluarga/lihatkeluarga')
                ->action(Web\Biodata\Sections\Keluarga\DetailAction::class)
                ->name('biodata-keluarga-lihatkeluarga'),
            Route::methods(['GET', 'POST'], '/biodata/keluarga/ums-nonums')
                ->action(Web\Biodata\Sections\Keluarga\FormAction::class)
                ->name('biodata-keluarga-ums-nonums'),
            Route::methods(['GET', 'POST'], '/biodata/keluarga/update')
                ->action(Web\Biodata\Sections\Keluarga\FormAction::class)
                ->name('biodata-keluarga-update'),
            Route::post('/biodata/keluarga/delete')
                ->action(Web\Biodata\Sections\Keluarga\DeleteAction::class)
                ->name('biodata-keluarga-delete'),
            Route::get('/biodata/bidangkepakaran/view')
                ->action(Web\Biodata\Sections\BidangKepakaran\ViewAction::class)
                ->name('biodata-bidangkepakaran-view'),
            Route::get('/biodata/bidangkepakaran/adminview')
                ->action(Web\Biodata\Sections\BidangKepakaran\ViewAction::class)
                ->name('biodata-bidangkepakaran-adminview'),
            Route::get('/biodata/bidangkepakaran/lihatbidangkepakaran')
                ->action(Web\Biodata\Sections\BidangKepakaran\DetailAction::class)
                ->name('biodata-bidangkepakaran-lihatbidangkepakaran'),
            Route::methods(['GET', 'POST'], '/biodata/bidangkepakaran/tambahbidangkepakaran')
                ->action(Web\Biodata\Sections\BidangKepakaran\FormAction::class)
                ->name('biodata-bidangkepakaran-tambahbidangkepakaran'),
            Route::methods(['GET', 'POST'], '/biodata/bidangkepakaran/update')
                ->action(Web\Biodata\Sections\BidangKepakaran\FormAction::class)
                ->name('biodata-bidangkepakaran-update'),
            Route::post('/biodata/bidangkepakaran/delete')
                ->action(Web\Biodata\Sections\BidangKepakaran\DeleteAction::class)
                ->name('biodata-bidangkepakaran-delete'),
            Route::get('/biodata/vaksinasi/index')
                ->action(Web\Biodata\Sections\Vaksinasi\ViewAction::class)
                ->name('biodata-vaksinasi-index'),
            Route::get('/biodata/vaksinasi/admin-view-status-vaksinasi')
                ->action(Web\Biodata\Sections\Vaksinasi\ViewAction::class)
                ->name('biodata-vaksinasi-admin-view-status-vaksinasi'),
            Route::methods(['GET', 'POST'], '/biodata/vaksinasi/view-st-vaksinasi')
                ->action(Web\Biodata\Sections\Vaksinasi\FormAction::class)
                ->name('biodata-vaksinasi-view-st-vaksinasi'),
            Route::methods(['GET', 'POST'], '/biodata/vaksinasi/update-status-vaksinasi')
                ->action(Web\Biodata\Sections\Vaksinasi\FormAction::class)
                ->name('biodata-vaksinasi-update-status-vaksinasi'),
            Route::methods(['GET', 'POST'], '/biodata/vaksinasi/update-st-vaksinasi')
                ->action(Web\Biodata\Sections\Vaksinasi\FormAction::class)
                ->name('biodata-vaksinasi-update-st-vaksinasi'),
            Route::get('/biodata/kelayakan-profesional/view')
                ->action(Web\Biodata\Sections\KelayakanProfesional\ViewAction::class)
                ->name('biodata-kelayakan-profesional-view'),
            Route::get('/biodata/kelayakan-profesional/admin-view')
                ->action(Web\Biodata\Sections\KelayakanProfesional\ViewAction::class)
                ->name('biodata-kelayakan-profesional-admin-view'),
            Route::get('/biodata/kelayakan-profesional/view-cc')
                ->action(Web\Biodata\Sections\KelayakanProfesional\DetailAction::class)
                ->name('biodata-kelayakan-profesional-view-cc'),
            Route::methods(['GET', 'POST'], '/biodata/kelayakan-profesional/add-cl-cert')
                ->action(Web\Biodata\Sections\KelayakanProfesional\FormAction::class)
                ->name('biodata-kelayakan-profesional-add-cl-cert'),
            Route::methods(['GET', 'POST'], '/biodata/kelayakan-profesional/update-cc')
                ->action(Web\Biodata\Sections\KelayakanProfesional\FormAction::class)
                ->name('biodata-kelayakan-profesional-update-cc'),
            Route::post('/biodata/kelayakan-profesional/delete-cc')
                ->action(Web\Biodata\Sections\KelayakanProfesional\DeleteAction::class)
                ->name('biodata-kelayakan-profesional-delete-cc'),
            Route::get('/biodata/bahasa/view')
                ->action(Web\Biodata\Sections\Bahasa\ViewAction::class)
                ->name('biodata-bahasa-view'),
            Route::get('/biodata/bahasa/adminview')
                ->action(Web\Biodata\Sections\Bahasa\ViewAction::class)
                ->name('biodata-bahasa-adminview'),
            Route::get('/biodata/bahasa/lihatbahasa')
                ->action(Web\Biodata\Sections\Bahasa\DetailAction::class)
                ->name('biodata-bahasa-lihatbahasa'),
            Route::methods(['GET', 'POST'], '/biodata/bahasa/tambahbahasa')
                ->action(Web\Biodata\Sections\Bahasa\FormAction::class)
                ->name('biodata-bahasa-tambahbahasa'),
            Route::methods(['GET', 'POST'], '/biodata/bahasa/update')
                ->action(Web\Biodata\Sections\Bahasa\FormAction::class)
                ->name('biodata-bahasa-update'),
            Route::post('/biodata/bahasa/delete')
                ->action(Web\Biodata\Sections\Bahasa\DeleteAction::class)
                ->name('biodata-bahasa-delete'),
            Route::get('/biodata/anugerah/view')
                ->action(Web\Biodata\Sections\Anugerah\ViewAction::class)
                ->name('biodata-anugerah-view'),
            Route::get('/biodata/anugerah/adminview')
                ->action(Web\Biodata\Sections\Anugerah\ViewAction::class)
                ->name('biodata-anugerah-adminview'),
            Route::get('/biodata/anugerah/lihatanugerah')
                ->action(Web\Biodata\Sections\Anugerah\DetailAction::class)
                ->name('biodata-anugerah-lihatanugerah'),
            Route::methods(['GET', 'POST'], '/biodata/anugerah/tambahanugerah')
                ->action(Web\Biodata\Sections\Anugerah\FormAction::class)
                ->name('biodata-anugerah-tambahanugerah'),
            Route::methods(['GET', 'POST'], '/biodata/anugerah/update')
                ->action(Web\Biodata\Sections\Anugerah\FormAction::class)
                ->name('biodata-anugerah-update'),
            Route::post('/biodata/anugerah/delete')
                ->action(Web\Biodata\Sections\Anugerah\DeleteAction::class)
                ->name('biodata-anugerah-delete'),
            Route::get('/biodata/pengalamankerja/view')
                ->action(Web\Biodata\Sections\PengalamanKerja\ViewAction::class)
                ->name('biodata-pengalamankerja-view'),
            Route::get('/biodata/pengalamankerja/adminview')
                ->action(Web\Biodata\Sections\PengalamanKerja\ViewAction::class)
                ->name('biodata-pengalamankerja-adminview'),
            Route::get('/biodata/pengalamankerja/lihatpengalamankerja')
                ->action(Web\Biodata\Sections\PengalamanKerja\DetailAction::class)
                ->name('biodata-pengalamankerja-lihatpengalamankerja'),
            Route::methods(['GET', 'POST'], '/biodata/pengalamankerja/tambahpengalamankerja')
                ->action(Web\Biodata\Sections\PengalamanKerja\FormAction::class)
                ->name('biodata-pengalamankerja-tambahpengalamankerja'),
            Route::methods(['GET', 'POST'], '/biodata/pengalamankerja/update')
                ->action(Web\Biodata\Sections\PengalamanKerja\FormAction::class)
                ->name('biodata-pengalamankerja-update'),
            Route::post('/biodata/pengalamankerja/delete')
                ->action(Web\Biodata\Sections\PengalamanKerja\DeleteAction::class)
                ->name('biodata-pengalamankerja-delete'),
            Route::get('/biodata/sejarahperubatan/view')
                ->action(Web\Biodata\Sections\SejarahPerubatan\ViewAction::class)
                ->name('biodata-sejarahperubatan-view'),
            Route::get('/biodata/sejarahperubatan/adminview')
                ->action(Web\Biodata\Sections\SejarahPerubatan\ViewAction::class)
                ->name('biodata-sejarahperubatan-adminview'),
            Route::get('/biodata/sejarahperubatan/lihatsejarahperubatan')
                ->action(Web\Biodata\Sections\SejarahPerubatan\DetailAction::class)
                ->name('biodata-sejarahperubatan-lihatsejarahperubatan'),
            Route::methods(['GET', 'POST'], '/biodata/sejarahperubatan/tambahsejarahperubatan')
                ->action(Web\Biodata\Sections\SejarahPerubatan\FormAction::class)
                ->name('biodata-sejarahperubatan-tambahsejarahperubatan'),
            Route::methods(['GET', 'POST'], '/biodata/sejarahperubatan/update')
                ->action(Web\Biodata\Sections\SejarahPerubatan\FormAction::class)
                ->name('biodata-sejarahperubatan-update'),
            Route::post('/biodata/sejarahperubatan/delete')
                ->action(Web\Biodata\Sections\SejarahPerubatan\DeleteAction::class)
                ->name('biodata-sejarahperubatan-delete'),
            Route::get('/badan-profesional/view')
                ->action(Web\Biodata\Sections\Keahlian\ViewAction::class)
                ->name('badan-profesional-view'),
            Route::get('/badan-profesional/adminview')
                ->action(Web\Biodata\Sections\Keahlian\ViewAction::class)
                ->name('badan-profesional-adminview'),
            Route::methods(['GET', 'POST'], '/badan-profesional/tambahbadanprofesional')
                ->action(Web\Biodata\Sections\Keahlian\FormAction::class)
                ->name('badan-profesional-tambahbadanprofesional'),
            Route::get('/badan-profesional/lihatbadanprofesional')
                ->action(Web\Biodata\Sections\Keahlian\DetailAction::class)
                ->name('badan-profesional-lihatbadanprofesional'),
            Route::methods(['GET', 'POST'], '/badan-profesional/update')
                ->action(Web\Biodata\Sections\Keahlian\FormAction::class)
                ->name('badan-profesional-update'),
            Route::post('/badan-profesional/delete')
                ->action(Web\Biodata\Sections\Keahlian\DeleteAction::class)
                ->name('badan-profesional-delete'),
            Route::get('/alamat/view')
                ->action(Web\Biodata\Sections\Alamat\ViewAction::class)
                ->name('alamat-view'),
            Route::get('/alamat/adminview')
                ->action(Web\Biodata\Sections\Alamat\ViewAction::class)
                ->name('alamat-adminview'),
            Route::get('/alamat/lihatalamat')
                ->action(Web\Biodata\Sections\Alamat\DetailAction::class)
                ->name('alamat-lihatalamat'),
            Route::methods(['GET', 'POST'], '/alamat/tambahalamat')
                ->action(Web\Biodata\Sections\Alamat\FormAction::class)
                ->name('alamat-tambahalamat'),
            Route::methods(['GET', 'POST'], '/alamat/update')
                ->action(Web\Biodata\Sections\Alamat\FormAction::class)
                ->name('alamat-update'),
            Route::post('/alamat/delete')
                ->action(Web\Biodata\Sections\Alamat\DeleteAction::class)
                ->name('alamat-delete'),
            Route::methods(['GET', 'POST'], '/alamat/statelist')
                ->action(Web\Biodata\Sections\Alamat\JsonListAction::class)
                ->name('alamat-statelist'),
            Route::methods(['GET', 'POST'], '/alamat/citylist')
                ->action(Web\Biodata\Sections\Alamat\JsonListAction::class)
                ->name('alamat-citylist'),
            Route::get('/pendidikan/view')
                ->action(Web\Biodata\Sections\Pendidikan\ViewAction::class)
                ->name('pendidikan-view'),
            Route::get('/pendidikan/adminview')
                ->action(Web\Biodata\Sections\Pendidikan\ViewAction::class)
                ->name('pendidikan-adminview'),
            Route::get('/pendidikan/lihatpendidikan')
                ->action(Web\Biodata\Sections\Pendidikan\DetailAction::class)
                ->name('pendidikan-lihatpendidikan'),
            Route::methods(['GET', 'POST'], '/pendidikan/tambahpendidikan')
                ->action(Web\Biodata\Sections\Pendidikan\FormAction::class)
                ->name('pendidikan-tambahpendidikan'),
            Route::methods(['GET', 'POST'], '/pendidikan/update')
                ->action(Web\Biodata\Sections\Pendidikan\FormAction::class)
                ->name('pendidikan-update'),
            Route::post('/pendidikan/delete')
                ->action(Web\Biodata\Sections\Pendidikan\DeleteAction::class)
                ->name('pendidikan-delete'),
            Route::get('/pasport-permit/view')
                ->action(Web\Biodata\Sections\PasportPermit\ViewAction::class)
                ->name('pasport-permit-view'),
            Route::get('/pasport-permit/adminview')
                ->action(Web\Biodata\Sections\PasportPermit\ViewAction::class)
                ->name('pasport-permit-adminview'),
            Route::get('/pasport-permit/lihatpasport')
                ->action(Web\Biodata\Sections\PasportPermit\DetailAction::class)
                ->name('pasport-permit-lihatpasport'),
            Route::get('/pasport-permit/lihatpermitkerja')
                ->action(Web\Biodata\Sections\PasportPermit\DetailAction::class)
                ->name('pasport-permit-lihatpermitkerja'),
            Route::methods(['GET', 'POST'], '/pasport-permit/tambahpasport')
                ->action(Web\Biodata\Sections\PasportPermit\FormAction::class)
                ->name('pasport-permit-tambahpasport'),
            Route::methods(['GET', 'POST'], '/pasport-permit/tambahpermitkerja')
                ->action(Web\Biodata\Sections\PasportPermit\FormAction::class)
                ->name('pasport-permit-tambahpermitkerja'),
            Route::methods(['GET', 'POST'], '/pasport-permit/updatepasport')
                ->action(Web\Biodata\Sections\PasportPermit\FormAction::class)
                ->name('pasport-permit-updatepasport'),
            Route::methods(['GET', 'POST'], '/pasport-permit/updatepermitkerja')
                ->action(Web\Biodata\Sections\PasportPermit\FormAction::class)
                ->name('pasport-permit-updatepermitkerja'),
            Route::post('/pasport-permit/deletepaspot')
                ->action(Web\Biodata\Sections\PasportPermit\DeleteAction::class)
                ->name('pasport-permit-deletepaspot'),
            Route::post('/pasport-permit/deletepermit')
                ->action(Web\Biodata\Sections\PasportPermit\DeleteAction::class)
                ->name('pasport-permit-deletepermit'),
            Route::get('/lesen/view')
                ->action(Web\Biodata\Sections\Lesen\ViewAction::class)
                ->name('lesen-view'),
            Route::get('/lesen/adminview')
                ->action(Web\Biodata\Sections\Lesen\ViewAction::class)
                ->name('lesen-adminview'),
            Route::get('/lesen/lihatlesen')
                ->action(Web\Biodata\Sections\Lesen\DetailAction::class)
                ->name('lesen-lihatlesen'),
            Route::methods(['GET', 'POST'], '/lesen/tambahlesen')
                ->action(Web\Biodata\Sections\Lesen\FormAction::class)
                ->name('lesen-tambahlesen'),
            Route::methods(['GET', 'POST'], '/lesen/update')
                ->action(Web\Biodata\Sections\Lesen\FormAction::class)
                ->name('lesen-update'),
            Route::get('/akaun/senarai-akaun')
                ->action(Web\Biodata\Sections\Akaun\ViewAction::class)
                ->name('akaun-senarai-akaun'),
            Route::get('/akaun/admin-senarai-akaun')
                ->action(Web\Biodata\Sections\Akaun\ViewAction::class)
                ->name('akaun-admin-senarai-akaun'),
            Route::get('/akaun/lihat')
                ->action(Web\Biodata\Sections\Akaun\DetailAction::class)
                ->name('akaun-lihat'),
            Route::get('/akaun/permohonan-penukaran-akaun-gaji')
                ->action(Web\Biodata\Sections\Akaun\RequestListAction::class)
                ->name('akaun-permohonan-penukaran-akaun-gaji'),
            Route::methods(['GET', 'POST'], '/akaun/mohon-tukar-akaun')
                ->action(Web\Biodata\Sections\Akaun\RequestFormAction::class)
                ->name('akaun-mohon-tukar-akaun'),
            Route::get('/akaun/lihat-acc')
                ->action(Web\Biodata\Sections\Akaun\RequestDetailAction::class)
                ->name('akaun-lihat-acc'),
            Route::methods(['GET', 'POST'], '/akaun/admin-tambah')
                ->action(Web\Biodata\Sections\Akaun\FormAction::class)
                ->name('akaun-admin-tambah'),
            Route::methods(['GET', 'POST'], '/akaun/admin-kemaskini')
                ->action(Web\Biodata\Sections\Akaun\FormAction::class)
                ->name('akaun-admin-kemaskini'),
            Route::get('/kecacatan/view')
                ->action(Web\Biodata\Sections\Kecacatan\ViewAction::class)
                ->name('kecacatan-view'),
            Route::get('/kecacatan/adminview')
                ->action(Web\Biodata\Sections\Kecacatan\ViewAction::class)
                ->name('kecacatan-adminview'),
            Route::get('/kecacatan/lihatkecacatan')
                ->action(Web\Biodata\Sections\Kecacatan\DetailAction::class)
                ->name('kecacatan-lihatkecacatan'),
            Route::methods(['GET', 'POST'], '/kecacatan/tambahkecacatan')
                ->action(Web\Biodata\Sections\Kecacatan\FormAction::class)
                ->name('kecacatan-tambahkecacatan'),
            Route::methods(['GET', 'POST'], '/kecacatan/update')
                ->action(Web\Biodata\Sections\Kecacatan\FormAction::class)
                ->name('kecacatan-update'),
            Route::post('/kecacatan/delete')
                ->action(Web\Biodata\Sections\Kecacatan\DeleteAction::class)
                ->name('kecacatan-delete'),
            Route::get('/keluarga/view')
                ->action(Web\Biodata\Sections\Keluarga\ViewAction::class)
                ->name('keluarga-view'),
            Route::get('/keluarga/adminview')
                ->action(Web\Biodata\Sections\Keluarga\ViewAction::class)
                ->name('keluarga-adminview'),
            Route::get('/keluarga/lihatkeluarga')
                ->action(Web\Biodata\Sections\Keluarga\DetailAction::class)
                ->name('keluarga-lihatkeluarga'),
            Route::methods(['GET', 'POST'], '/keluarga/ums-nonums')
                ->action(Web\Biodata\Sections\Keluarga\FormAction::class)
                ->name('keluarga-ums-nonums'),
            Route::methods(['GET', 'POST'], '/keluarga/update')
                ->action(Web\Biodata\Sections\Keluarga\FormAction::class)
                ->name('keluarga-update'),
            Route::post('/keluarga/delete')
                ->action(Web\Biodata\Sections\Keluarga\DeleteAction::class)
                ->name('keluarga-delete'),
            Route::get('/bidangkepakaran/view')
                ->action(Web\Biodata\Sections\BidangKepakaran\ViewAction::class)
                ->name('bidangkepakaran-view'),
            Route::get('/bidangkepakaran/adminview')
                ->action(Web\Biodata\Sections\BidangKepakaran\ViewAction::class)
                ->name('bidangkepakaran-adminview'),
            Route::get('/bidangkepakaran/lihatbidangkepakaran')
                ->action(Web\Biodata\Sections\BidangKepakaran\DetailAction::class)
                ->name('bidangkepakaran-lihatbidangkepakaran'),
            Route::methods(['GET', 'POST'], '/bidangkepakaran/tambahbidangkepakaran')
                ->action(Web\Biodata\Sections\BidangKepakaran\FormAction::class)
                ->name('bidangkepakaran-tambahbidangkepakaran'),
            Route::methods(['GET', 'POST'], '/bidangkepakaran/update')
                ->action(Web\Biodata\Sections\BidangKepakaran\FormAction::class)
                ->name('bidangkepakaran-update'),
            Route::post('/bidangkepakaran/delete')
                ->action(Web\Biodata\Sections\BidangKepakaran\DeleteAction::class)
                ->name('bidangkepakaran-delete'),
            Route::get('/vaksinasi/index')
                ->action(Web\Biodata\Sections\Vaksinasi\ViewAction::class)
                ->name('vaksinasi-index'),
            Route::get('/vaksinasi/admin-view-status-vaksinasi')
                ->action(Web\Biodata\Sections\Vaksinasi\ViewAction::class)
                ->name('vaksinasi-admin-view-status-vaksinasi'),
            Route::methods(['GET', 'POST'], '/vaksinasi/view-st-vaksinasi')
                ->action(Web\Biodata\Sections\Vaksinasi\FormAction::class)
                ->name('vaksinasi-view-st-vaksinasi'),
            Route::methods(['GET', 'POST'], '/vaksinasi/update-status-vaksinasi')
                ->action(Web\Biodata\Sections\Vaksinasi\FormAction::class)
                ->name('vaksinasi-update-status-vaksinasi'),
            Route::methods(['GET', 'POST'], '/vaksinasi/update-st-vaksinasi')
                ->action(Web\Biodata\Sections\Vaksinasi\FormAction::class)
                ->name('vaksinasi-update-st-vaksinasi'),
            Route::get('/kelayakan-profesional/view')
                ->action(Web\Biodata\Sections\KelayakanProfesional\ViewAction::class)
                ->name('kelayakan-profesional-view'),
            Route::get('/kelayakan-profesional/admin-view')
                ->action(Web\Biodata\Sections\KelayakanProfesional\ViewAction::class)
                ->name('kelayakan-profesional-admin-view'),
            Route::get('/kelayakan-profesional/view-cc')
                ->action(Web\Biodata\Sections\KelayakanProfesional\DetailAction::class)
                ->name('kelayakan-profesional-view-cc'),
            Route::methods(['GET', 'POST'], '/kelayakan-profesional/add-cl-cert')
                ->action(Web\Biodata\Sections\KelayakanProfesional\FormAction::class)
                ->name('kelayakan-profesional-add-cl-cert'),
            Route::methods(['GET', 'POST'], '/kelayakan-profesional/update-cc')
                ->action(Web\Biodata\Sections\KelayakanProfesional\FormAction::class)
                ->name('kelayakan-profesional-update-cc'),
            Route::post('/kelayakan-profesional/delete-cc')
                ->action(Web\Biodata\Sections\KelayakanProfesional\DeleteAction::class)
                ->name('kelayakan-profesional-delete-cc'),
            Route::get('/bahasa/view')
                ->action(Web\Biodata\Sections\Bahasa\ViewAction::class)
                ->name('bahasa-view'),
            Route::get('/bahasa/adminview')
                ->action(Web\Biodata\Sections\Bahasa\ViewAction::class)
                ->name('bahasa-adminview'),
            Route::get('/bahasa/lihatbahasa')
                ->action(Web\Biodata\Sections\Bahasa\DetailAction::class)
                ->name('bahasa-lihatbahasa'),
            Route::methods(['GET', 'POST'], '/bahasa/tambahbahasa')
                ->action(Web\Biodata\Sections\Bahasa\FormAction::class)
                ->name('bahasa-tambahbahasa'),
            Route::methods(['GET', 'POST'], '/bahasa/update')
                ->action(Web\Biodata\Sections\Bahasa\FormAction::class)
                ->name('bahasa-update'),
            Route::post('/bahasa/delete')
                ->action(Web\Biodata\Sections\Bahasa\DeleteAction::class)
                ->name('bahasa-delete'),
            Route::get('/anugerah/view')
                ->action(Web\Biodata\Sections\Anugerah\ViewAction::class)
                ->name('anugerah-view'),
            Route::get('/anugerah/adminview')
                ->action(Web\Biodata\Sections\Anugerah\ViewAction::class)
                ->name('anugerah-adminview'),
            Route::get('/anugerah/lihatanugerah')
                ->action(Web\Biodata\Sections\Anugerah\DetailAction::class)
                ->name('anugerah-lihatanugerah'),
            Route::methods(['GET', 'POST'], '/anugerah/tambahanugerah')
                ->action(Web\Biodata\Sections\Anugerah\FormAction::class)
                ->name('anugerah-tambahanugerah'),
            Route::methods(['GET', 'POST'], '/anugerah/update')
                ->action(Web\Biodata\Sections\Anugerah\FormAction::class)
                ->name('anugerah-update'),
            Route::post('/anugerah/delete')
                ->action(Web\Biodata\Sections\Anugerah\DeleteAction::class)
                ->name('anugerah-delete'),
            Route::get('/pengalamankerja/view')
                ->action(Web\Biodata\Sections\PengalamanKerja\ViewAction::class)
                ->name('pengalamankerja-view'),
            Route::get('/pengalamankerja/adminview')
                ->action(Web\Biodata\Sections\PengalamanKerja\ViewAction::class)
                ->name('pengalamankerja-adminview'),
            Route::get('/pengalamankerja/lihatpengalamankerja')
                ->action(Web\Biodata\Sections\PengalamanKerja\DetailAction::class)
                ->name('pengalamankerja-lihatpengalamankerja'),
            Route::methods(['GET', 'POST'], '/pengalamankerja/tambahpengalamankerja')
                ->action(Web\Biodata\Sections\PengalamanKerja\FormAction::class)
                ->name('pengalamankerja-tambahpengalamankerja'),
            Route::methods(['GET', 'POST'], '/pengalamankerja/update')
                ->action(Web\Biodata\Sections\PengalamanKerja\FormAction::class)
                ->name('pengalamankerja-update'),
            Route::post('/pengalamankerja/delete')
                ->action(Web\Biodata\Sections\PengalamanKerja\DeleteAction::class)
                ->name('pengalamankerja-delete'),
            Route::get('/sejarahperubatan/view')
                ->action(Web\Biodata\Sections\SejarahPerubatan\ViewAction::class)
                ->name('sejarahperubatan-view'),
            Route::get('/sejarahperubatan/adminview')
                ->action(Web\Biodata\Sections\SejarahPerubatan\ViewAction::class)
                ->name('sejarahperubatan-adminview'),
            Route::get('/sejarahperubatan/lihatsejarahperubatan')
                ->action(Web\Biodata\Sections\SejarahPerubatan\DetailAction::class)
                ->name('sejarahperubatan-lihatsejarahperubatan'),
            Route::methods(['GET', 'POST'], '/sejarahperubatan/tambahsejarahperubatan')
                ->action(Web\Biodata\Sections\SejarahPerubatan\FormAction::class)
                ->name('sejarahperubatan-tambahsejarahperubatan'),
            Route::methods(['GET', 'POST'], '/sejarahperubatan/update')
                ->action(Web\Biodata\Sections\SejarahPerubatan\FormAction::class)
                ->name('sejarahperubatan-update'),
            Route::post('/sejarahperubatan/delete')
                ->action(Web\Biodata\Sections\SejarahPerubatan\DeleteAction::class)
                ->name('sejarahperubatan-delete'),
            Route::methods(['GET', 'POST'], '/biodata/citylist')
                ->action(Web\Biodata\JsonListAction::class)
                ->name('biodata-citylist'),
            Route::methods(['GET', 'POST'], '/biodata/peringkatcapaiankedua')
                ->action(Web\Biodata\JsonListAction::class)
                ->name('biodata-peringkatcapaiankedua'),
            Route::methods(['GET', 'POST'], '/biodata/generate')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-generate'),
            Route::methods(['GET', 'POST'], '/biodata/kemaskinipenetapanpengguna')
                ->action(Web\RecordAction\Action::class)
                ->name('biodata-kemaskinipenetapanpengguna'),
            Route::get('/gambar-profile/semakan')
                ->action(Web\GambarProfile\Staff\RequestListAction::class)
                ->name('gambar-profile-semakan'),
            Route::methods(['GET', 'POST'], '/gambar-profile/gambar')
                ->action(Web\GambarProfile\Staff\UploadAction::class)
                ->name('gambar-profile-gambar'),
            Route::get('/gambar-profile/lihat-permohonan')
                ->action(Web\GambarProfile\Staff\DetailAction::class)
                ->name('gambar-profile-lihat-permohonan'),
            Route::get('/gambar-profile/senarai')
                ->action(Web\GambarProfile\Admin\PendingListAction::class)
                ->name('gambar-profile-senarai'),
            Route::get('/gambar-profile/rekod-permohonan')
                ->action(Web\GambarProfile\Admin\HistoryAction::class)
                ->name('gambar-profile-rekod-permohonan'),
            Route::methods(['GET', 'POST'], '/gambar-profile/semakan-gambar')
                ->action(Web\GambarProfile\Admin\ReviewAction::class)
                ->name('gambar-profile-semakan-gambar'),
            Route::methods(['GET', 'POST'], '/gambar-profile/cari-kakitangan')
                ->action(Web\GambarProfile\Admin\StaffSearchAction::class)
                ->name('gambar-profile-cari-kakitangan'),
            Route::methods(['GET', 'POST'], '/gambar-profile/muatnaik-gambar-kakitangan')
                ->action(Web\GambarProfile\Admin\CounterUploadAction::class)
                ->name('gambar-profile-muatnaik-gambar-kakitangan'),
            Route::get('/gambar-profile/admin-senarai-penukaran')
                ->action(Web\GambarProfile\Admin\CounterHistoryAction::class)
                ->name('gambar-profile-admin-senarai-penukaran'),
            Route::get('/gambar-profile/admin-view-penukaran')
                ->action(Web\GambarProfile\Admin\CounterDetailAction::class)
                ->name('gambar-profile-admin-view-penukaran'),
            Route::methods(['GET', 'POST'], '/gambar-profile/tambah-admin')
                ->action(Web\GambarProfile\Admin\AdminFormAction::class)
                ->name('gambar-profile-tambah-admin'),
            Route::get('/gambar-profile/senarai-admin')
                ->action(Web\GambarProfile\Admin\AdminListAction::class)
                ->name('gambar-profile-senarai-admin'),
            Route::get('/gambar-profile/lihat-admin')
                ->action(Web\GambarProfile\Admin\AdminDetailAction::class)
                ->name('gambar-profile-lihat-admin'),
            Route::methods(['GET', 'POST'], '/gambar-profile/kemaskini-admin')
                ->action(Web\GambarProfile\Admin\AdminFormAction::class)
                ->name('gambar-profile-kemaskini-admin'),
            Route::methods(['GET', 'POST'], '/biodata/resetpassword')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-resetpassword'),
            Route::methods(['GET', 'POST'], '/biodata/change-my-password')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-change-my-password'),
            Route::methods(['GET', 'POST'], '/biodata/logout')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-logout'),
            Route::methods(['GET', 'POST'], '/biodata/admin-carian-staf')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-admin-carian-staf'),
            Route::methods(['GET', 'POST'], '/biodata/confirmtosave')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-confirmtosave'),
            Route::methods(['GET', 'POST'], '/biodata/kemaskinikakitangan')
                ->action(Web\Biodata\FormAction::class)
                ->name('biodata-kemaskinikakitangan'),
            Route::methods(['GET', 'POST'], '/biodata/kemaskini')
                ->action(Web\Biodata\FormAction::class)
                ->name('biodata-kemaskini'),
            Route::methods(['GET', 'POST'], '/biodata/padamkakitangan')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-padamkakitangan'),
            Route::methods(['GET', 'POST'], '/biodata/kemaskinikakitangan-kprogram')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-kemaskinikakitangan-kprogram'),
            Route::methods(['GET', 'POST'], '/biodata/sync-to-ad')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-sync-to-ad'),
            Route::methods(['GET', 'POST'], '/biodata/sync-to-grp')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-sync-to-grp'),
            Route::methods(['GET', 'POST'], '/biodata/sync-to-grp-non-admin')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-sync-to-grp-non-admin'),
            Route::methods(['GET', 'POST'], '/biodata/sync-to-grp-all')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-sync-to-grp-all'),
            Route::methods(['GET', 'POST'], '/biodata/grp-inactivate-staff')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-grp-inactivate-staff'),
            Route::methods(['GET', 'POST'], '/biodata/recreate-ad')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-recreate-ad'),
            Route::methods(['GET', 'POST'], '/external-login')
                ->action(Web\ExternalLogin\Action::class)
                ->name('external-login'),
        ),
];
