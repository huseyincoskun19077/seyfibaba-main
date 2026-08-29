-keep class com.github.chinloyal.pusher_client.** { *; }

# --- Google Play Core SplitInstall support (for deferred components) ---
#-keep class com.google.android.play.core.splitcompat.SplitCompatApplication { *; }
-keep class com.google.android.play.core.splitinstall.** { *; }
-keep class com.google.android.play.core.tasks.** { *; }
-dontwarn com.google.android.play.core.**

# --------------------------------------
# SLF4J - Used by Stripe
# --------------------------------------
-keep class org.slf4j.** { *; }
-dontwarn org.slf4j.**