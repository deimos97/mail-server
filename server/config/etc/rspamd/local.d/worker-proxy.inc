milter = yes;
timeout = 120s;
bind_socket = "localhost:11332";
upstream "local" {
  default = yes;
  self_scan = yes;
}
