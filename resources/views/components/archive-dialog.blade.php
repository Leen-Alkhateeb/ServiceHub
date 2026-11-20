<dialog class="hub-dialog archive-dialog" id="archive-dialog" aria-labelledby="archive-title" aria-describedby="archive-message">
    <h2 id="archive-title">تأكيد الحذف</h2>
    <p id="archive-message"></p>
    <form method="POST">
        @csrf
        @method('DELETE')
        <div class="moderation-actions">
            <button class="btn btn-outline" type="button" data-close-dialog autofocus>إلغاء</button>
            <button class="btn btn-danger" type="submit">تأكيد الحذف</button>
        </div>
    </form>
</dialog>
