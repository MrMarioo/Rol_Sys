<template>
    <div class="px-sm-0 px-4">
        <div class="d-flex justify-content-between">
            <div>
                <h3 class="h5">
                    <slot name="title"></slot>
                </h3>

                <p class="text-muted mt-1">
                    <slot name="description"></slot>
                </p>
            </div>

            <div>
                <slot name="aside"></slot>
            </div>
        </div>
    </div>
</template>
