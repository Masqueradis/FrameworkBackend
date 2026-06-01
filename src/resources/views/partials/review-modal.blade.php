<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ $action }}" method="POST">
                @csrf
                @if(isset($method) && $method !== 'POST')
                    @method($method)
                @endif

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if($comment && $comment->status === \App\Enums\CommentStatus::Pending)
                        <div class="alert alert-warning py-2 mb-3 small">
                            Your review is pending moderation. You can update it below.
                        </div>
                    @elseif($comment && $comment->status === \App\Enums\CommentStatus::Rejected)
                        <div class="alert alert-danger py-2 mb-3 small">
                            Your previous review was rejected. Please edit it to comply with our guidelines and submit for re-moderation.
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted d-block">Rating <span class="text-danger">*</span></label>
                        <div class="star-rating position-relative">
                            @for($i = 5; $i >= 1; $i--)
                                <input type="radio" id="star{{ $i }}_{{ $id }}" name="rating" value="{{ $i }}" required {{ old('rating', $comment?->rating) == $i ? 'checked' : '' }} />
                                <label for="star{{ $i }}_{{ $id }}" title="{{ $i }} stars">★</label>
                            @endfor
                        </div>
                        @error('rating')
                        <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="content_{{ $id }}" class="form-label fw-bold text-muted">Your Review <span class="text-danger">*</span></label>
                        <textarea name="content" id="content_{{ $id }}" rows="4"
                                  class="form-control @error('content') is-invalid @enderror"
                                  required>{{ old('content', $comment?->content) }}</textarea>
                        @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
    <style>
        .star-rating {
            display: inline-flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            font-size: 2.5rem;
            line-height: 1;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            color: #e4e5e9;
            cursor: pointer;
            transition: color 0.2s ease-in-out;
            padding-right: 5px;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
        }
        .star-rating label:active {
            transform: scale(0.9);
        }
    </style>
@endonce
