<form action="" method="post">
    <div class="save-container">
        <div class="saveable">
            <div class="panel v2">
                <div class="setting-group">
                    <h3>Email options</h3>

                    <div class="setting text">
                        <label for="$ctlInput1">
                            Subject
                            <span class="help tooltip" title="The subject to use for contact message emails. The $name variable will be replaced with the sender's name."><i class="material-icons">&#xE8FD;</i>
                        </label>
                        <div class="text">
                            <input id="$ctlInput1" type="text" name="contact-subject" value="<?php echo sanitize_attribute($this->getopt('contact-subject')); ?>" placeholder="Enter message subject...">
                        </div>
                    </div>

                    <div class="setting text">
                        <label for="$ctlInput2">
                            Send messages to
                            <span class="help tooltip" title="Enter your email address here. This is where messages from the contact form will be sent to."><i class="material-icons">&#xE8FD;</i>
                        </label>
                        <div class="text">
                            <input id="$ctlInput2" type="text" name="contact-email-sendto" value="<?php echo sanitize_attribute($this->getopt('contact-email-sendto')); ?>" placeholder="Enter your email address...">
                        </div>
                    </div>
                </div>

                <div class="setting-group">
                    <h3>Contact options</h3>

                    <div class="setting toggle">
                        <label for="$ctlInput3">
                            Block messages that look like spam
                            <span class="help tooltip" title="Note: You should disable this if you are running a serious website."><i class="material-icons">&#xE8FD;</i>
                        </label>
                        <div class="switch" id="ctlInput3">
                            <input type="hidden" name="contact-block-spam" value="<?php echo $this->getopt('contact-block-spam'); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting toggle">
                        <label for="$ctlInput4">
                            Block flood attempts
                            <span class="help tooltip" title="This will prevent visitors from sending a large number of emails in a short amount of time."><i class="material-icons">&#xE8FD;</i>
                        </label>
                        <div class="switch" id="ctlInput4">
                            <input type="hidden" name="contact-block-speed" value="<?php echo $this->getopt('contact-block-speed'); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="save">
            <div class="save-box">
                <button type="submit">
                    <span>Save changes</span>
                    <span>Saved</span>
                    <img src="../../resources/images/load32.gif" width="16px" height="16px">
                </button>
            </div>
        </div>
    </div>
</form>

<div class="panel" style="border-top: 1px solid #eee;">
    <h3>More contact options</h3>

    <div style="padding: 3px 0;">
        <a class="btn" href="translate.php?name=plugin.contact">Edit contact page translations</a>
    </div>
    <div style="padding: 3px 0;">
        <a class="btn" href="settings/mail.php">Edit mail settings</a>
    </div>
</div>
