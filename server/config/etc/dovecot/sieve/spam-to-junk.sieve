# Global, runs before any user script: file Rspamd "add header" verdicts into Junk.
require ["fileinto", "mailbox"];
if header :is "X-Spam" "yes" {
    fileinto :create "Junk";
    stop;
}
