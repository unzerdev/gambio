<h2 class="underline overline">{$COMMENT_TITLE}</h2>

{if !empty($ERROR_MESSAGE)}
	<p class="unzercw-error" style="color: #a94442; background-color: #f2dede; border-color: #ebccd1; padding: 15px; margin-bottom: 20px;nborder: 1px solid transparent; border-radius: 4px;">{$ERROR_MESSAGE}</p>
{/if}

<div class="unzercw-comment-textarea-box order_payment">
	<textarea name="comments" id="comments_textarea" cols="60" rows="5">{$commentContent}</textarea>
</div>