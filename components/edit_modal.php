<!-- Модальне вікно для редагування -->
<div class="event-modal" id="editEventModal">
    <div class="event-modal-content edit-modal">
        <span class="close-modal" onclick="eventModalManager.closeEditModal()">&times;</span>
        <h3>Редагувати подію</h3>

        <form id="editEventForm" enctype="multipart/form-data">
            <input type="hidden" id="editEventId" name="id">

            <div class="form-group">
                <label for="editTitle">Назва події:</label>
                <input type="text" id="editTitle" name="title" required>
            </div>

            <div class="form-group">
                <label for="editCategory">Категорія:</label>
                <select id="editCategory" name="category">
                    <option value="">Оберіть категорію</option>
                    <option value="Футбол">Футбол</option>
                    <option value="Концерт">Концерт</option>
                    <option value="Зустріч">Зустріч</option>
                    <option value="Навчання">Навчання</option>
                    <option value="Прогулянка">Прогулянка</option>
                    <option value="Вечірка">Вечірка</option>
                    <option value="Інше">Інше</option>
                </select>
            </div>

            <div class="form-group">
                <label for="editLocation">Локація:</label>
                <input type="text" id="editLocation" name="location">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="editDate">Дата:</label>
                    <input type="date" id="editDate" name="event_date" required>
                </div>

                <div class="form-group">
                    <label for="editTime">Час:</label>
                    <input type="time" id="editTime" name="event_time">
                </div>
            </div>

            <div class="form-group">
                <label for="editDescription">Опис події:</label>
                <textarea id="editDescription" name="description" rows="5"></textarea>
            </div>

            <div class="form-group">
                <label for="editImage">Зображення:</label>
                <input type="file" id="editImage" name="image" accept="image/*">
                <div id="currentImagePreview" class="image-preview"></div>
            </div>

            <div class="form-buttons">
                <button type="button" class="btn btn-cancel" onclick="eventModalManager.closeEditModal()">Скасувати</button>
                <button type="submit" class="btn btn-save">Зберегти зміни</button>
            </div>
        </form>
    </div>
</div>