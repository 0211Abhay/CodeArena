<style>
    .form-footer {
        background-color: white;
        text-align: center;
        padding: 15px;
        color: var(--gray);
        font-size: 14px;
        border-top: 1px solid var(--gray-light);
        box-sizing: border-box;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .form-footer {
            padding: 10px;
            font-size: 12px;
        }
    }

    @media (max-width: 480px) {
        .form-footer {
            padding: 8px;
            font-size: 12px;
        }
    }
</style>

<div class="form-footer">
    &copy; <?php echo date("Y"); ?> Exam Registration Portal. All rights reserved.
</div>
