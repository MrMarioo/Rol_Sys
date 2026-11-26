<template>
    <div class="d-flex justify-content-between">
        <div>
            <h5 class="title mb-1">
                <slot name="title" />
            </h5>

            <p class="text-soft small mb-0">
                <slot name="description" />
            </p>
        </div>

        <div>
            <slot name="aside" />
        </div>
    </div>
</template>
