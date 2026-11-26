<template>
    <div class="auth-page d-flex flex-column align-items-center justify-content-center">
        <div class="mb-4">
            <slot name="logo" />
        </div>

        <div class="nk-auth-body">
            <div class="card card-bordered">
                <div class="card-inner card-inner-lg">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.auth-page {
    min-height: 100vh;
    padding: 3rem 0;
    background-color: #f5f6fa;
}
</style>
