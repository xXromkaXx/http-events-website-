<div class="event-modal" id="eventModal">
    <div class="event-modal-content">
        <span class="close-modal">&times;</span>

        <div class="event-header">
            <img id="modalImage" src="" alt="">
            <div class="event-title-block">
                <h3 id="modalTitle"></h3>
                <p class="modal-category"></p>
            </div>
        </div>

        <!-- Блок автора - просто праворуч -->
        <div class="author-badge" id="authorBadge" style="display: none;">
            <span class="author-icon">👤</span>
            <span class="author-name" id="modalAuthorName"></span>
        </div>

        <div class="event-info-grid">
            <div class="info-item"> <span class="modal-location"></span></div>
            <div class="info-item"> <span class="modal-date"></span></div>
            <div class="info-item"> <span class="modal-time"></span></div>
        </div>

        <div class="event-description">
            <h4>Про подію</h4>
        </div>
        <p id="modalDescription"></p>
    </div>
</div>