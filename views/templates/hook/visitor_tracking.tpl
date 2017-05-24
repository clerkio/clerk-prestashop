<!-- Start of Clerk.io E-commerce Personalisation tool - www.clerk.io -->
<script type="text/javascript">
    window.clerkAsyncInit = function() {
        Clerk.config({
            key: '{$clerk_public_key}',
            collect_email: {$clerk_datasync_collect_emails}
        });
    };

    (function(){
        var e = document.createElement('script'); e.type='text/javascript'; e.async = true;
        e.src = document.location.protocol + '//api.clerk.io/static/clerk.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(e, s);
    })();
</script>
<!-- End of Clerk.io E-commerce Personalisation tool - www.clerk.io -->
